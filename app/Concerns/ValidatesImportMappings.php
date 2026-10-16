<?php

namespace App\Concerns;

use App\Enums\ColumnDataType;
use App\Models\ImportMapping;
use App\Rules\SafeColumnName;
use App\Rules\SafeTableName;
use App\Services\Sheets\Exceptions\ValueConversionException;
use App\Services\Sheets\SheetData;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Shared validation for import mapping forms, including the nested
 * destination column configuration.
 */
trait ValidatesImportMappings
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function mappingRules(?int $sheetTabId = null, ?ImportMapping $ignore = null): array
    {
        $tableName = ['required', 'string', new SafeTableName];

        if ($sheetTabId !== null) {
            $tableName[] = Rule::unique('import_mappings', 'table_name')
                ->where('sheet_tab_id', $sheetTabId)
                ->ignore($ignore);
        }

        return [
            'table_name' => $tableName,
            'enabled' => ['boolean'],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*.source_column' => ['required', 'string', 'max:255'],
            'columns.*.destination_column' => ['required', 'string', new SafeColumnName],
            'columns.*.data_type' => ['required', Rule::enum(ColumnDataType::class)],
            'columns.*.nullable' => ['boolean'],
            'columns.*.default_value' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Validate cross-field constraints that depend on the whole column list.
     */
    protected function validateColumnConfiguration(Validator $validator): void
    {
        if ($validator->errors()->hasAny(['columns', 'table_name'])) {
            return;
        }

        $columns = $this->input('columns');

        if (! is_array($columns)) {
            return;
        }

        $seenSources = [];
        $seenDestinations = [];

        foreach ($columns as $index => $column) {
            if (! is_array($column)) {
                continue;
            }

            $source = $column['source_column'] ?? null;
            $destination = $column['destination_column'] ?? null;

            if (is_string($source) && $source !== '') {
                $key = SheetData::normalizeHeader($source);

                if (isset($seenSources[$key])) {
                    $validator->errors()->add("columns.$index.source_column", "The source column [$source] is configured more than once.");
                }

                $seenSources[$key] = true;
            }

            if (is_string($destination) && $destination !== '') {
                if (isset($seenDestinations[$destination])) {
                    $validator->errors()->add("columns.$index.destination_column", "The destination column [$destination] is configured more than once.");
                }

                $seenDestinations[$destination] = true;
            }

            $this->validateDefaultValue($validator, $index, $column);
        }
    }

    /**
     * @param  array<string, mixed>  $column
     */
    private function validateDefaultValue(Validator $validator, int $index, array $column): void
    {
        $default = $column['default_value'] ?? null;

        if (! is_string($default) || $default === '') {
            return;
        }

        $type = $column['data_type'] ?? null;

        if (! is_string($type)) {
            return;
        }

        $dataType = ColumnDataType::tryFrom($type);

        if (! $dataType instanceof ColumnDataType) {
            return;
        }

        try {
            $dataType->castValue($default);
        } catch (ValueConversionException $exception) {
            $validator->errors()->add("columns.$index.default_value", "The default value cannot be converted: {$exception->getMessage()}");
        }
    }
}
