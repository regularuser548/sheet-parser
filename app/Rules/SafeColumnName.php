<?php

namespace App\Rules;

use Closure;

/**
 * A safe destination column identifier.
 */
class SafeColumnName extends SafeIdentifier
{
    protected function validateIdentifier(string $value, Closure $fail): void
    {
        if (mb_strtolower($value) === 'id') {
            $fail('The :attribute column is managed by the importer and cannot be configured.');
        }
    }
}
