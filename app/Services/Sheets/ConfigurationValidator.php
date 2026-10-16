<?php

namespace App\Services\Sheets;

use App\Models\ColumnMapping;
use App\Models\ImportMapping;
use App\Models\SheetSource;
use App\Models\SheetTab;
use App\Services\Sheets\Contracts\SheetReader;
use App\Services\Sheets\Exceptions\SheetReadException;
use App\Services\Sheets\Exceptions\ValueConversionException;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Validates the import configuration before an import runs.
 *
 * Identifiers, data types, and required mapping fields are always checked.
 * When fetching is enabled the configured tabs are read so that missing
 * source headers are reported too.
 */
class ConfigurationValidator
{
    /**
     * Google spreadsheet identifiers are URL-safe and reasonably long.
     */
    private const SPREADSHEET_ID_PATTERN = '/^[A-Za-z0-9_-]{10,}$/';

    public function __construct(
        private readonly SheetReader $reader,
        private readonly DestinationSchemaManager $schema,
    ) {}

    /**
     * Validate every configured source.
     */
    public function validateAll(bool $fetch = true): ValidationReport
    {
        $sources = SheetSource::query()->orderBy('id')->get();
        $issues = [];

        if ($sources->isEmpty()) {
            $issues[] = new ValidationIssue('warning', 'Configuration', 'No spreadsheet sources have been configured yet.');
        }

        foreach ($sources as $source) {
            $issues = [...$issues, ...$this->validateSource($source, $fetch)];
        }

        return new ValidationReport($issues);
    }

    /**
     * Validate a single source and its tabs, mappings, and columns.
     *
     * @return list<ValidationIssue>
     */
    public function validateSource(SheetSource $source, bool $fetch = true): array
    {
        $source->loadMissing(['tabs.importMappings.columnMappings']);

        $issues = [];

        if (preg_match(self::SPREADSHEET_ID_PATTERN, $source->spreadsheet_id) !== 1) {
            $issues[] = new ValidationIssue(
                'error',
                $source->name,
                "The spreadsheet ID [{$source->spreadsheet_id}] does not look like a Google Sheets file ID.",
            );
        }

        if (! $source->enabled) {
            $issues[] = new ValidationIssue('warning', $source->name, 'The source is disabled and will be skipped by imports.');
        }

        if ($source->tabs->isEmpty()) {
            $issues[] = new ValidationIssue('warning', $source->name, 'The source has no tabs configured.');
        }

        foreach ($source->tabs as $tab) {
            $issues = [...$issues, ...$this->validateTab($source, $tab, $fetch)];
        }

        return $issues;
    }

    /**
     * @return list<ValidationIssue>
     */
    private function validateTab(SheetSource $source, SheetTab $tab, bool $fetch): array
    {
        $subject = "{$source->name} / {$tab->name}";
        $issues = [];

        if (! $tab->enabled) {
            $issues[] = new ValidationIssue('warning', $subject, 'The tab is disabled and will be skipped by imports.');
        }

        $mappings = $tab->importMappings;

        if ($mappings->isEmpty()) {
            $issues[] = new ValidationIssue('warning', $subject, 'The tab has no destination mappings configured.');

            return $issues;
        }

        foreach ($mappings as $mapping) {
            $issues = [...$issues, ...$this->validateMapping($source, $tab, $mapping)];
        }

        if ($fetch && $tab->enabled) {
            $issues = [...$issues, ...$this->validateHeaders($source, $tab, $mappings)];
        }

        return $issues;
    }

    /**
     * @return list<ValidationIssue>
     */
    private function validateMapping(SheetSource $source, SheetTab $tab, ImportMapping $mapping): array
    {
        $subject = "{$source->name} / {$tab->name} / {$mapping->table_name}";
        $issues = [];

        if (! $mapping->enabled) {
            $issues[] = new ValidationIssue('warning', $subject, 'The mapping is disabled and will be skipped by imports.');
        }

        if ($mapping->columnMappings->isEmpty()) {
            $issues[] = new ValidationIssue('error', $subject, 'The mapping has no columns configured.');

            return $issues;
        }

        try {
            $this->schema->assertSafeTable($mapping->table_name);
        } catch (Throwable $exception) {
            $issues[] = new ValidationIssue('error', $subject, $exception->getMessage());

            return $issues;
        }

        $seenSources = [];
        $seenDestinations = [];

        foreach ($mapping->columnMappings as $column) {
            $issues = [...$issues, ...$this->validateColumn($subject, $column, $seenSources, $seenDestinations)];
        }

        $issues = [...$issues, ...$this->validateSchemaState($subject, $mapping)];

        return $issues;
    }

    /**
     * @param  array<string, bool>  $seenSources
     * @param  array<string, bool>  $seenDestinations
     * @return list<ValidationIssue>
     */
    private function validateColumn(string $subject, ColumnMapping $column, array &$seenSources, array &$seenDestinations): array
    {
        $issues = [];

        try {
            $this->schema->assertSafeColumn($column->destination_column);
        } catch (Throwable $exception) {
            $issues[] = new ValidationIssue('error', $subject, $exception->getMessage());

            return $issues;
        }

        $sourceKey = SheetData::normalizeHeader($column->source_column);

        if (isset($seenSources[$sourceKey])) {
            $issues[] = new ValidationIssue('error', $subject, "The source column [{$column->source_column}] is configured more than once.");
        }

        if (isset($seenDestinations[$column->destination_column])) {
            $issues[] = new ValidationIssue('error', $subject, "The destination column [{$column->destination_column}] is configured more than once.");
        }

        $seenSources[$sourceKey] = true;
        $seenDestinations[$column->destination_column] = true;

        if ($column->default_value !== null) {
            try {
                $column->data_type->castValue($column->default_value);
            } catch (ValueConversionException $exception) {
                $issues[] = new ValidationIssue(
                    'error',
                    $subject,
                    "The default value for [{$column->destination_column}] cannot be converted: {$exception->getMessage()}",
                );
            }
        }

        return $issues;
    }

    /**
     * Report destination schema changes that an import would perform.
     *
     * @return list<ValidationIssue>
     */
    private function validateSchemaState(string $subject, ImportMapping $mapping): array
    {
        $plan = $this->schema->plan(
            $mapping->table_name,
            $mapping->columnMappings->keyBy('destination_column')->all(),
        );

        if (! $plan->tableExists) {
            return [new ValidationIssue('warning', $subject, "The destination table [{$mapping->table_name}] does not exist yet and will be created on import.")];
        }

        if ($plan->missingColumns !== []) {
            return [new ValidationIssue(
                'warning',
                $subject,
                'The destination columns ['.implode(', ', $plan->missingColumns).'] do not exist yet and will be added on import.',
            )];
        }

        return [];
    }

    /**
     * Read the tab and verify every configured source column exists.
     *
     * @param  Collection<int, ImportMapping>  $mappings
     * @return list<ValidationIssue>
     */
    private function validateHeaders(SheetSource $source, SheetTab $tab, $mappings): array
    {
        $subject = "{$source->name} / {$tab->name}";

        try {
            $data = $this->reader->read($source, $tab);
        } catch (SheetReadException $exception) {
            return [new ValidationIssue('error', $subject, $exception->getMessage())];
        }

        $positions = $data->headerPositions();
        $missing = [];

        foreach ($mappings as $mapping) {
            if (! $mapping->enabled) {
                continue;
            }

            foreach ($mapping->columnMappings as $column) {
                if (! array_key_exists(SheetData::normalizeHeader($column->source_column), $positions)) {
                    $missing["{$column->source_column} ({$mapping->table_name})"] = true;
                }
            }
        }

        if ($missing !== []) {
            return [new ValidationIssue(
                'error',
                $subject,
                'The configured source columns ['.implode(', ', array_keys($missing)).'] were not found in the tab header row.',
            )];
        }

        return [];
    }
}
