<?php

namespace App\Models;

use Database\Factories\ImportMappingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $sheet_tab_id
 * @property string $table_name
 * @property bool $enabled
 */
#[Fillable(['table_name', 'enabled'])]
class ImportMapping extends Model
{
    /** @use HasFactory<ImportMappingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<SheetTab, $this>
     */
    public function tab(): BelongsTo
    {
        return $this->belongsTo(SheetTab::class, 'sheet_tab_id');
    }

    /**
     * @return HasMany<ColumnMapping, $this>
     */
    public function columnMappings(): HasMany
    {
        return $this->hasMany(ColumnMapping::class);
    }
}
