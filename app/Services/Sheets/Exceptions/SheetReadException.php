<?php

namespace App\Services\Sheets\Exceptions;

use RuntimeException;

class SheetReadException extends RuntimeException
{
    public const CATEGORY_API = 'api';

    public const CATEGORY_CREDENTIALS = 'credentials';

    public const CATEGORY_PERMISSION = 'permission';

    public const CATEGORY_SPREADSHEET_NOT_FOUND = 'spreadsheet_not_found';

    public const CATEGORY_TAB_NOT_FOUND = 'tab_not_found';

    public function __construct(
        string $message,
        public readonly string $category = self::CATEGORY_API,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
