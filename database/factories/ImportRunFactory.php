<?php

namespace Database\Factories;

use App\Enums\ImportRunStatus;
use App\Models\ImportRun;
use App\Models\SheetSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportRun>
 */
class ImportRunFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sheet_source_id' => SheetSource::factory(),
            'status' => ImportRunStatus::Success,
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
            'imported_rows' => 0,
            'updated_rows' => 0,
            'failed_rows' => 0,
            'message' => null,
            'details' => null,
        ];
    }

    /**
     * Indicate that the run is still in progress.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImportRunStatus::Running,
            'finished_at' => null,
        ]);
    }

    /**
     * Indicate that the run failed.
     */
    public function failed(string $message = 'Import failed.'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImportRunStatus::Failed,
            'finished_at' => now(),
            'message' => $message,
        ]);
    }
}
