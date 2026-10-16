<?php

namespace App\Services\Sheets;

use App\Enums\ImportRunStatus;
use App\Models\ColumnMapping;
use App\Models\ImportMapping;
use App\Models\ImportRowMarker;
use App\Models\ImportRun;
use App\Models\SheetSource;
use App\Models\SheetTab;
use App\Services\Sheets\Contracts\SheetReader;
use App\Services\Sheets\Exceptions\SheetReadException;
use App\Services\Sheets\Exceptions\UnsafeIdentifierException;
use App\Services\Sheets\Exceptions\ValueConversionException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Runs the import pipeline: read enabled tabs, resolve configured headers
 * to destination columns, ensure the destination schema, and write rows
 * idempotently.
 *
 * Each spreadsheet source is processed independently so a failure in one
 * source never prevents the others from importing.
 */
class SheetImporter
{
    /**
     * Maximum number of issues stored per run before they are summarized.
     */
    private const MAX_ISSUES = 50;

    public function __construct(
        private readonly SheetReader $reader,
        private readonly DestinationSchemaManager $schema,
    ) {}

    /**
     * Import all enabled sources, or a single source when provided.
     *
     * @return Collection<int, ImportRun>
     */
    public function importAll(?SheetSource $source = null): Collection
    {
        $sources = $source instanceof SheetSource
            ? collect([$source])
            : SheetSource::query()->where('enabled', true)->orderBy('id')->get();

        return $sources->map(fn (SheetSource $source): ImportRun => $this->import($source));
    }

    /**
     * Import a single source and always return the recorded run.
     */
    public function import(SheetSource $source): ImportRun
    {
        $source->loadMissing(['tabs.importMappings.columnMappings']);

        $run = $source->importRuns()->create([
            'status' => ImportRunStatus::Running,
            'started_at' => now(),
        ]);

        $issues = [];
        $mappingStats = [];
        $counters = ['inserted' => 0, 'updated' => 0, 'failed' => 0];

        try {
            foreach ($source->tabs->where('enabled', true) as $tab) {
                $this->importTab($source, $tab, $issues, $mappingStats, $counters);
            }
        } catch (Throwable $exception) {
            report($exception);

            $issues[] = $this->issue('error', $source->name, $exception->getMessage());
        }

        $run->update([
            'status' => $this->statusFor($issues, $counters),
            'finished_at' => now(),
            'imported_rows' => $counters['inserted'],
            'updated_rows' => $counters['updated'],
            'failed_rows' => $counters['failed'],
            'message' => $this->summaryMessage($issues),
            'details' => [
                'issues' => array_slice($issues, 0, self::MAX_ISSUES),
                'mappings' => $mappingStats,
            ],
        ]);

        return $run;
    }

    /**
     * Import all enabled mappings of a single tab.
     *
     * @param  list<array{level: string, subject: string, message: string}>  $issues
     * @param  list<array{table: string, inserted: int, updated: int, skipped: int, failed: int}>  $mappingStats
     * @param  array{inserted: int, updated: int, failed: int}  $counters
     */
    private function importTab(
        SheetSource $source,
        SheetTab $tab,
        array &$issues,
        array &$mappingStats,
        array &$counters,
    ): void {
        $mappings = $tab->importMappings
            ->where('enabled', true)
            ->filter(fn (ImportMapping $mapping): bool => $mapping->columnMappings->isNotEmpty());

        if ($mappings->isEmpty()) {
            $issues[] = $this->issue(
                'warning',
                "{$source->name} / {$tab->name}",
                'The tab has no enabled destination mappings with configured columns.',
            );

            return;
        }

        try {
            $data = $this->reader->read($source, $tab);
        } catch (SheetReadException $exception) {
            $issues[] = $this->issue('error', "{$source->name} / {$tab->name}", $exception->getMessage());
            $counters['failed'] += $mappings->count();

            return;
        }

        $positions = $data->headerPositions();

        foreach ($mappings as $mapping) {
            $stats = $this->importMapping($source, $tab, $mapping, $data, $positions, $issues);

            $mappingStats[] = $stats;
            $counters['inserted'] += $stats['inserted'];
            $counters['updated'] += $stats['updated'];
            $counters['failed'] += $stats['failed'];
        }
    }

    /**
     * Write every data row of a tab into one destination table.
     *
     * @param  array<string, int>  $positions
     * @param  list<array{level: string, subject: string, message: string}>  $issues
     * @return array{table: string, inserted: int, updated: int, skipped: int, failed: int}
     */
    private function importMapping(
        SheetSource $source,
        SheetTab $tab,
        ImportMapping $mapping,
        SheetData $data,
        array $positions,
        array &$issues,
    ): array {
        $stats = ['table' => $mapping->table_name, 'inserted' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
        $subject = "{$source->name} / {$tab->name} / {$mapping->table_name}";

        try {
            [$resolved, $missing] = $this->resolveColumns($mapping, $positions);
        } catch (UnsafeIdentifierException $exception) {
            $issues[] = $this->issue('error', $subject, $exception->getMessage());

            return $stats;
        }

        if ($missing !== []) {
            $issues[] = $this->issue(
                'error',
                $subject,
                'The configured source columns ['.implode(', ', $missing).'] were not found in the tab header row.',
            );
        }

        if ($resolved === []) {
            $issues[] = $this->issue('error', $subject, 'None of the configured source columns matched the tab headers.');

            return $stats;
        }

        try {
            $this->schema->ensureDestination($mapping->table_name, $mapping->columnMappings->keyBy('destination_column')->all());
        } catch (Throwable $exception) {
            $issues[] = $this->issue('error', $subject, $exception->getMessage());
            $stats['failed'] = count($data->rows);

            return $stats;
        }

        $primaryKey = $this->schema->autoIncrementingPrimaryKey($mapping->table_name);

        if ($primaryKey === null) {
            $issues[] = $this->issue(
                'error',
                $subject,
                "Destination table [{$mapping->table_name}] must have an auto-incrementing primary key.",
            );

            return $stats;
        }

        /** @var Collection<int, ImportRowMarker> $markers */
        $markers = $tab->rowMarkers()
            ->where('table_name', $mapping->table_name)
            ->get()
            ->keyBy('row_index');

        foreach ($data->rows as $offset => $row) {
            $rowIndex = $offset + 1;

            try {
                $payload = $this->buildPayload($resolved, $row);

                if ($this->isEmptyRow($payload)) {
                    continue;
                }

                $hash = hash('sha256', (string) json_encode($payload, JSON_UNESCAPED_UNICODE));
                $marker = $markers->get($rowIndex);

                if ($marker instanceof ImportRowMarker && hash_equals($marker->row_hash, $hash)) {
                    $stats['skipped']++;

                    continue;
                }

                if ($marker instanceof ImportRowMarker && $marker->target_id !== null) {
                    DB::table($mapping->table_name)->where($primaryKey, $marker->target_id)->update($payload);
                    $marker->update(['row_hash' => $hash]);
                    $stats['updated']++;

                    continue;
                }

                $targetId = DB::table($mapping->table_name)->insertGetId($payload);

                ImportRowMarker::updateOrCreate(
                    ['sheet_tab_id' => $tab->id, 'table_name' => $mapping->table_name, 'row_index' => $rowIndex],
                    ['row_hash' => $hash, 'target_id' => $targetId],
                );

                $stats['inserted']++;
            } catch (ValueConversionException|QueryException $exception) {
                $stats['failed']++;
                $issues[] = $this->issue('error', $subject, "Row #{$rowIndex}: {$exception->getMessage()}");
            }
        }

        return $stats;
    }

    /**
     * Resolve configured source columns to their position in the header row.
     *
     * @param  array<string, int>  $positions
     * @return array{0: array<string, array{column: ColumnMapping, position: int}>, 1: list<string>}
     */
    private function resolveColumns(ImportMapping $mapping, array $positions): array
    {
        $resolved = [];
        $missing = [];

        foreach ($mapping->columnMappings as $column) {
            $this->schema->assertSafeTable($mapping->table_name);
            $this->schema->assertSafeColumn($column->destination_column);

            $key = SheetData::normalizeHeader($column->source_column);

            if (! array_key_exists($key, $positions)) {
                $missing[] = $column->source_column;

                continue;
            }

            $resolved[$column->destination_column] = [
                'column' => $column,
                'position' => $positions[$key],
            ];
        }

        return [$resolved, $missing];
    }

    /**
     * Convert a raw sheet row into a destination payload.
     *
     * @param  array<string, array{column: ColumnMapping, position: int}>  $resolved
     * @param  list<string|null>  $row
     * @return array<string, int|float|bool|string|array<mixed>|null>
     */
    private function buildPayload(array $resolved, array $row): array
    {
        $payload = [];

        foreach ($resolved as $destination => $target) {
            $value = $target['column']->data_type->castValue($row[$target['position']] ?? null);

            if ($value === null && ! $target['column']->nullable) {
                if ($target['column']->default_value === null) {
                    throw new ValueConversionException("The column [$destination] is required but the value is empty.");
                }

                $value = $target['column']->data_type->castValue($target['column']->default_value);
            }

            $payload[$destination] = $value;
        }

        return $payload;
    }

    /**
     * @param  array<string, int|float|bool|string|array<mixed>|null>  $payload
     */
    private function isEmptyRow(array $payload): bool
    {
        foreach ($payload as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{level: string, subject: string, message: string}
     */
    private function issue(string $level, string $subject, string $message): array
    {
        return ['level' => $level, 'subject' => $subject, 'message' => $message];
    }

    /**
     * @param  list<array{level: string, subject: string, message: string}>  $issues
     * @param  array{inserted: int, updated: int, failed: int}  $counters
     */
    private function statusFor(array $issues, array $counters): ImportRunStatus
    {
        $hasError = collect($issues)->contains(fn (array $issue): bool => $issue['level'] === 'error');

        if (! $hasError) {
            return ImportRunStatus::Success;
        }

        $processed = $counters['inserted'] + $counters['updated'];

        return $processed > 0 ? ImportRunStatus::Partial : ImportRunStatus::Failed;
    }

    /**
     * @param  list<array{level: string, subject: string, message: string}>  $issues
     */
    private function summaryMessage(array $issues): ?string
    {
        $errors = collect($issues)->where('level', 'error');

        if ($errors->isEmpty()) {
            return null;
        }

        $summarized = $errors->take(3)
            ->map(fn (array $issue): string => "{$issue['subject']}: {$issue['message']}")
            ->implode(' | ');

        $remaining = $errors->count() - 3;

        return $remaining > 0 ? "$summarized | and $remaining more error(s)." : $summarized;
    }
}
