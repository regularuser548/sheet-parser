<?php

namespace App\Rules;

use App\Services\Sheets\DestinationSchemaManager;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Verifies that a value is a safe, unquoted database identifier.
 */
abstract class SafeIdentifier implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! DestinationSchemaManager::isValidIdentifier($value)) {
            $fail('The :attribute must be a valid database identifier (letters, numbers, and underscores, starting with a letter or underscore).');

            return;
        }

        $this->validateIdentifier($value, $fail);
    }

    /**
     * Run any additional rule-specific checks.
     */
    abstract protected function validateIdentifier(string $value, Closure $fail): void;
}
