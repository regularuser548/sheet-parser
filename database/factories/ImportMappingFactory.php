<?php

namespace Database\Factories;

use App\Models\ImportMapping;
use App\Models\SheetTab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportMapping>
 */
class ImportMappingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sheet_tab_id' => SheetTab::factory(),
            'table_name' => fake()->unique()->regexify('[a-z][a-z0-9_]{3,20}'),
            'enabled' => true,
        ];
    }

    /**
     * Indicate that the mapping is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => false,
        ]);
    }
}
