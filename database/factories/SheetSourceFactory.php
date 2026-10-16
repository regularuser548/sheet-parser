<?php

namespace Database\Factories;

use App\Models\SheetSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SheetSource>
 */
class SheetSourceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'spreadsheet_id' => fake()->unique()->regexify('[A-Za-z0-9_-]{44}'),
            'credentials_path' => null,
            'enabled' => true,
        ];
    }

    /**
     * Indicate that the source is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => false,
        ]);
    }
}
