<?php

namespace App\Models;

use Database\Factories\SheetTabFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $sheet_source_id
 * @property string $name
 * @property bool $enabled
 */
#[Fillable(['name', 'enabled'])]
class SheetTab extends Model
{
    /** @use HasFactory<SheetTabFactory> */
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
     * @return BelongsTo<SheetSource, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(SheetSource::class, 'sheet_source_id');
    }

    /**
     * @return HasMany<ImportMapping, $this>
     */
    public function importMappings(): HasMany
    {
        return $this->hasMany(ImportMapping::class);
    }

    /**
     * @return HasMany<ImportRowMarker, $this>
     */
    public function rowMarkers(): HasMany
    {
        return $this->hasMany(ImportRowMarker::class);
    }
}
