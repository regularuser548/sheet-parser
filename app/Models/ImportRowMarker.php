<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks the last imported state of a spreadsheet row so repeated imports
 * are idempotent and edited rows update the destination row in place.
 *
 * @property int $id
 * @property int $sheet_tab_id
 * @property string $table_name
 * @property int $row_index
 * @property string $row_hash
 * @property int|null $target_id
 */
#[Fillable(['table_name', 'row_index', 'row_hash', 'target_id'])]
class ImportRowMarker extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'row_index' => 'integer',
            'target_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<SheetTab, $this>
     */
    public function tab(): BelongsTo
    {
        return $this->belongsTo(SheetTab::class, 'sheet_tab_id');
    }
}
