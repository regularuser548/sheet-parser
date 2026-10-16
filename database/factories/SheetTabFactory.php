<?php

namespace Database\Factories;

use App\Models\SheetSource;
use App\Models\SheetTab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SheetTab>
 */
class SheetTabFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sheet_source_id' => SheetSource::factory(),
            'name' => fake()->unique()->words(2, true),
            'enabled' => true,
        ];
    }

    /**
     * Indicate that the tab is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => false,
        ]);
    }
}
