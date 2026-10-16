<?php

namespace App\Models;

use App\Enums\ImportRunStatus;
use Database\Factories\ImportRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sheet_source_id
 * @property ImportRunStatus $status
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property int $imported_rows
 * @property int $updated_rows
 * @property int $failed_rows
 * @property string|null $message
 * @property array{issues?: list<array{level: string, subject: string, message: string}>, mappings?: list<array{table: string, inserted: int, updated: int, skipped: int, failed: int}>}|null $details
 */
#[Fillable([
    'status',
    'started_at',
    'finished_at',
    'imported_rows',
    'updated_rows',
    'failed_rows',
    'message',
    'details',
])]
class ImportRun extends Model
{
    /** @use HasFactory<ImportRunFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ImportRunStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'imported_rows' => 'integer',
            'updated_rows' => 'integer',
            'failed_rows' => 'integer',
            'details' => 'array',
        ];
    }

    /**
     * @return BelongsTo<SheetSource, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(SheetSource::class, 'sheet_source_id');
    }

    /**
     * Issues collected during the run, each with level, subject and message keys.
     *
     * @return list<array{level: string, subject: string, message: string}>
     */
    public function issues(): array
    {
        return $this->details['issues'] ?? [];
    }

    /**
     * Whether the run completed without any failures.
     */
    public function isSuccessful(): bool
    {
        return $this->status === ImportRunStatus::Success;
    }
}
