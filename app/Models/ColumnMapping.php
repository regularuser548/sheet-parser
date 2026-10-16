<?php

namespace App\Models;

use App\Enums\ColumnDataType;
use Database\Factories\ColumnMappingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $import_mapping_id
 * @property string $source_column
 * @property string $destination_column
 * @property ColumnDataType $data_type
 * @property bool $nullable
 * @property string|null $default_value
 */
#[Fillable(['source_column', 'destination_column', 'data_type', 'nullable', 'default_value'])]
class ColumnMapping extends Model
{
    /** @use HasFactory<ColumnMappingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_type' => ColumnDataType::class,
            'nullable' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ImportMapping, $this>
     */
    public function mapping(): BelongsTo
    {
        return $this->belongsTo(ImportMapping::class, 'import_mapping_id');
    }
}
