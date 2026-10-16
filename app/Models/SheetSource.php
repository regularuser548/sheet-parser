<?php

namespace App\Models;

use Database\Factories\SheetSourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $spreadsheet_id
 * @property string|null $credentials_path
 * @property bool $enabled
 */
#[Fillable(['name', 'spreadsheet_id', 'credentials_path', 'enabled'])]
class SheetSource extends Model
{
    /** @use HasFactory<SheetSourceFactory> */
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
     * @return HasMany<SheetTab, $this>
     */
    public function tabs(): HasMany
    {
        return $this->hasMany(SheetTab::class);
    }

    /**
     * @return HasMany<ImportRun, $this>
     */
    public function importRuns(): HasMany
    {
        return $this->hasMany(ImportRun::class)->latest('id');
    }

    /**
     * Resolve a source by spreadsheet ID or display name.
     */
    public static function findByIdentifier(string $identifier): ?self
    {
        return self::query()
            ->where('spreadsheet_id', $identifier)
            ->orWhere('name', $identifier)
            ->first();
    }
}
