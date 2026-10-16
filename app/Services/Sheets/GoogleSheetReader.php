<?php

namespace App\Services\Sheets;

use App\Models\SheetSource;
use App\Models\SheetTab;
use App\Services\Sheets\Contracts\SheetReader;
use App\Services\Sheets\Exceptions\SheetReadException;
use Google\Client as GoogleClient;
use Google\Service\Exception as GoogleServiceException;
use Google\Service\Sheets;
use GuzzleHttp\Client as GuzzleClient;
use Throwable;

/**
 * Reads spreadsheet tabs through the Google Sheets API using a service
 * account. Transient API failures are retried; permanent failures are
 * translated into messages that explain how to fix the configuration.
 */
class GoogleSheetReader implements SheetReader
{
    public function __construct(
        private readonly ?string $credentialsPath = null,
        private readonly int $timeout = 30,
        private readonly int $retries = 3,
    ) {}

    public function read(SheetSource $source, SheetTab $tab): SheetData
    {
        $service = new Sheets($this->createClient($source));
        $range = $this->quoteRange($tab->name);

        /** @var list<list<string|null>> $values */
        $values = $this->request(
            fn () => $service->spreadsheets_values->get(
                $source->spreadsheet_id,
                $range,
                ['valueRenderOption' => 'FORMATTED_VALUE', 'majorDimension' => 'ROWS'],
            )->getValues() ?? [],
            $source,
            $tab,
        );

        $headers = array_map(
            static fn (mixed $header): string => (string) ($header ?? ''),
            $values[0] ?? [],
        );

        return new SheetData($headers, array_slice($values, 1));
    }

    private function createClient(SheetSource $source): GoogleClient
    {
        $path = $this->credentialsPathFor($source);

        if ($path === null || ! is_readable($path)) {
            throw new SheetReadException(
                'Google service account credentials were not found. Set GOOGLE_SHEETS_CREDENTIALS to a readable JSON key file.',
                SheetReadException::CATEGORY_CREDENTIALS,
            );
        }

        $client = new GoogleClient;

        try {
            $client->setAuthConfig($path);
        } catch (Throwable $exception) {
            throw new SheetReadException(
                "Google service account credentials at [$path] are invalid: {$exception->getMessage()}",
                SheetReadException::CATEGORY_CREDENTIALS,
                $exception,
            );
        }

        $client->addScope(Sheets::SPREADSHEETS_READONLY);
        $client->setHttpClient(new GuzzleClient(['timeout' => $this->timeout]));

        return $client;
    }

    private function credentialsPathFor(SheetSource $source): ?string
    {
        $path = $source->credentials_path ?: $this->credentialsPath ?: config('services.google.sheets.credentials');

        return is_string($path) && $path !== '' ? $path : null;
    }

    /**
     * Execute an API call, retrying transient failures with exponential backoff.
     *
     * @template TValue
     *
     * @param  callable(): TValue  $callback
     * @return TValue
     */
    private function request(callable $callback, SheetSource $source, SheetTab $tab): mixed
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                return $callback();
            } catch (Throwable $exception) {
                if (! $this->isTransient($exception) || $attempt >= $this->retries) {
                    throw $this->translate($exception, $source, $tab);
                }

                usleep(100_000 * $attempt);
            }
        }
    }

    /**
     * Whether the failure is a temporary condition that is worth retrying.
     */
    private function isTransient(Throwable $exception): bool
    {
        $code = $this->httpStatus($exception);

        return in_array($code, [429, 500, 502, 503, 504], true);
    }

    private function httpStatus(Throwable $exception): int
    {
        if ($exception instanceof GoogleServiceException) {
            return (int) $exception->getCode();
        }

        $status = $exception->getCode();

        return is_int($status) ? $status : 0;
    }

    private function translate(Throwable $exception, SheetSource $source, SheetTab $tab): SheetReadException
    {
        $message = $exception->getMessage();
        $status = $this->httpStatus($exception);
        $spreadsheetId = $source->spreadsheet_id;

        if ($status === 404) {
            return new SheetReadException(
                "Spreadsheet [$spreadsheetId] was not found. Check the spreadsheet ID and share it with the service account email.",
                SheetReadException::CATEGORY_SPREADSHEET_NOT_FOUND,
                $exception,
            );
        }

        if ($status === 403) {
            return new SheetReadException(
                "Access to spreadsheet [$spreadsheetId] was denied. Share the spreadsheet with the service account email and try again.",
                SheetReadException::CATEGORY_PERMISSION,
                $exception,
            );
        }

        if (str_contains($message, 'Unable to parse range')) {
            return new SheetReadException(
                "Tab [{$tab->name}] was not found in spreadsheet [$spreadsheetId].",
                SheetReadException::CATEGORY_TAB_NOT_FOUND,
                $exception,
            );
        }

        return new SheetReadException(
            "The Google Sheets API rejected the request for spreadsheet [$spreadsheetId]: $message",
            SheetReadException::CATEGORY_API,
            $exception,
        );
    }

    private function quoteRange(string $tabName): string
    {
        return "'".str_replace("'", "''", $tabName)."'";
    }
}
