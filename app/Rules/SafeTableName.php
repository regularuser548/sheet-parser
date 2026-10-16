<?php

namespace App\Rules;

use App\Services\Sheets\DestinationSchemaManager;
use Closure;

/**
 * A safe destination table identifier that is not an application table.
 */
class SafeTableName extends SafeIdentifier
{
    protected function validateIdentifier(string $value, Closure $fail): void
    {
        if (DestinationSchemaManager::isProtectedTable($value)) {
            $fail('The :attribute table is reserved for the application and cannot be used as an import destination.');
        }
    }
}
