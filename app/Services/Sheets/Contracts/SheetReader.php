<?php

namespace App\Services\Sheets\Contracts;

use App\Models\SheetSource;
use App\Models\SheetTab;
use App\Services\Sheets\Exceptions\SheetReadException;
use App\Services\Sheets\SheetData;

interface SheetReader
{
    /**
     * Read the configured tab of a spreadsheet, treating the first row as headers.
     *
     * @throws SheetReadException When the spreadsheet, tab, or credentials cannot be read.
     */
    public function read(SheetSource $source, SheetTab $tab): SheetData;
}
