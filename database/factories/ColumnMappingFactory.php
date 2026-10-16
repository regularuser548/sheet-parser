<?php

namespace Database\Factories;

use App\Enums\ColumnDataType;
use App\Models\ColumnMapping;
use App\Models\ImportMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ColumnMapping>
 */
class ColumnMappingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $column = fake()->unique()->lexify('column_????');

        return [
            'import_mapping_id' => ImportMapping::factory(),
            'source_column' => str_replace('_', ' ', $column),
            'destination_column' => $column,
            'data_type' => ColumnDataType::String,
            'nullable' => true,
            'default_value' => null,
        ];
    }
}
