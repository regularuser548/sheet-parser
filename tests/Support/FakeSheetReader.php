<?php

namespace Tests\Support;

use App\Models\SheetSource;
use App\Models\SheetTab;
use App\Services\Sheets\Contracts\SheetReader;
use App\Services\Sheets\Exceptions\SheetReadException;
use App\Services\Sheets\SheetData;

/**
 * In-memory {@see SheetReader} used by tests instead of the Google API.
 */
class FakeSheetReader implements SheetReader
{
    /**
     * @var array<string, SheetData>
     */
    private array $data = [];

    /**
     * @var array<string, SheetReadException>
     */
    private array $failures = [];

    /**
     * @param  list<string>  $headers
     * @param  list<list<string|null>>  $rows
     */
    public function withData(string $spreadsheetId, string $tabName, array $headers, array $rows): self
    {
        $this->data[$this->key($spreadsheetId, $tabName)] = new SheetData($headers, $rows);

        return $this;
    }

    public function failWith(string $spreadsheetId, string $tabName, string $message, string $category = SheetReadException::CATEGORY_API): self
    {
        $this->failures[$this->key($spreadsheetId, $tabName)] = new SheetReadException($message, $category);

        return $this;
    }

    public function read(SheetSource $source, SheetTab $tab): SheetData
    {
        $key = $this->key($source->spreadsheet_id, $tab->name);

        if (isset($this->failures[$key])) {
            throw $this->failures[$key];
        }

        if (! isset($this->data[$key])) {
            throw new SheetReadException(
                "No fake data configured for spreadsheet [{$source->spreadsheet_id}] tab [{$tab->name}].",
            );
        }

        return $this->data[$key];
    }

    private function key(string $spreadsheetId, string $tabName): string
    {
        return $spreadsheetId.'|'.$tabName;
    }
}
