<?php

namespace App\Http\Controllers\Sheets;

use App\Enums\ColumnDataType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sheets\StoreImportMappingRequest;
use App\Http\Requests\Sheets\UpdateImportMappingRequest;
use App\Http\Resources\ImportMappingResource;
use App\Http\Resources\SheetTabResource;
use App\Models\ImportMapping;
use App\Models\SheetTab;
use App\Services\Sheets\DestinationSchemaManager;
use App\Services\Sheets\Exceptions\UnsafeIdentifierException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ImportMappingController extends Controller
{
    /**
     * Show the mappings and destination columns configured for a tab.
     */
    public function index(SheetTab $tab, DestinationSchemaManager $schema): Response
    {
        $tab->load(['source', 'importMappings.columnMappings']);

        return Inertia::render('sheets/mappings/index', [
            'tab' => new SheetTabResource($tab),
            'dataTypes' => collect(ColumnDataType::cases())
                ->map(fn (ColumnDataType $type): array => ['value' => $type->value, 'label' => $type->label()])
                ->all(),
            'mappings' => $tab->importMappings
                ->sortBy('table_name')
                ->map(fn (ImportMapping $mapping): array => [
                    ...(new ImportMappingResource($mapping))->resolve(),
                    'schema' => $this->schemaStatus($schema, $mapping),
                ])
                ->values(),
        ]);
    }

    /**
     * Create a mapping and its destination columns.
     */
    public function store(StoreImportMappingRequest $request, SheetTab $tab): RedirectResponse
    {
        $mapping = DB::transaction(function () use ($request, $tab): ImportMapping {
            $mapping = $tab->importMappings()->create([
                'table_name' => $request->validated('table_name'),
                'enabled' => $request->boolean('enabled', true),
            ]);

            $mapping->columnMappings()->createMany($this->columns($request->validated('columns')));

            return $mapping;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Mapping for [{$mapping->table_name}] saved."]);

        return to_route('sheets.mappings.index', $tab);
    }

    /**
     * Update a mapping and replace its destination columns.
     */
    public function update(UpdateImportMappingRequest $request, ImportMapping $mapping): RedirectResponse
    {
        DB::transaction(function () use ($request, $mapping): void {
            $mapping->update([
                'table_name' => $request->validated('table_name'),
                'enabled' => $request->boolean('enabled', true),
            ]);

            $mapping->columnMappings()->delete();
            $mapping->columnMappings()->createMany($this->columns($request->validated('columns')));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Mapping for [{$mapping->table_name}] updated."]);

        return to_route('sheets.mappings.index', $mapping->sheet_tab_id);
    }

    /**
     * Delete a mapping.
     */
    public function destroy(ImportMapping $mapping): RedirectResponse
    {
        $tabId = $mapping->sheet_tab_id;
        $mapping->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mapping deleted.']);

        return to_route('sheets.mappings.index', $tabId);
    }

    /**
     * Normalize the submitted column rows for persistence.
     *
     * @param  array<int, array<string, mixed>>  $columns
     * @return list<array{source_column: string, destination_column: string, data_type: string, nullable: bool, default_value: string|null}>
     */
    private function columns(array $columns): array
    {
        return array_values(array_map(fn (array $column): array => [
            'source_column' => (string) $column['source_column'],
            'destination_column' => (string) $column['destination_column'],
            'data_type' => (string) $column['data_type'],
            'nullable' => (bool) ($column['nullable'] ?? false),
            'default_value' => (isset($column['default_value']) && $column['default_value'] !== '') ? (string) $column['default_value'] : null,
        ], $columns));
    }

    /**
     * Describe whether the destination schema matches the mapping.
     *
     * @return array{table: string, exists: bool, missing_columns: list<string>, satisfied: bool, error: string|null}
     */
    private function schemaStatus(DestinationSchemaManager $schema, ImportMapping $mapping): array
    {
        try {
            $plan = $schema->plan(
                $mapping->table_name,
                $mapping->columnMappings->keyBy('destination_column')->all(),
            );
        } catch (UnsafeIdentifierException $exception) {
            return [
                'table' => $mapping->table_name,
                'exists' => false,
                'missing_columns' => [],
                'satisfied' => false,
                'error' => $exception->getMessage(),
            ];
        }

        return [
            'table' => $plan->table,
            'exists' => $plan->tableExists,
            'missing_columns' => $plan->missingColumns,
            'satisfied' => $plan->isSatisfied(),
            'error' => null,
        ];
    }
}
