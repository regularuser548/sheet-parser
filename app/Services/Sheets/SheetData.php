<?php

namespace App\Services\Sheets;

final readonly class SheetData
{
    /**
     * @param  list<string>  $headers  Headers read from the first row of the tab.
     * @param  list<list<string|null>>  $rows  Data rows in sheet order, excluding the header row.
     */
    public function __construct(
        public array $headers,
        public array $rows,
    ) {}

    /**
     * Normalize a header so headers can be matched regardless of casing,
     * surrounding whitespace, or repeated inner whitespace.
     */
    public static function normalizeHeader(string $header): string
    {
        $normalized = mb_strtolower(trim($header));
        $normalized = (string) preg_replace('/\s+/u', ' ', $normalized);

        return $normalized;
    }

    /**
     * Map normalized headers to their zero-based column position.
     *
     * @return array<string, int>
     */
    public function headerPositions(): array
    {
        $positions = [];

        foreach ($this->headers as $position => $header) {
            $normalized = self::normalizeHeader($header);

            if ($normalized !== '' && ! array_key_exists($normalized, $positions)) {
                $positions[$normalized] = $position;
            }
        }

        return $positions;
    }
}
