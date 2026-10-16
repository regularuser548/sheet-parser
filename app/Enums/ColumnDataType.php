<?php

namespace App\Enums;

use App\Services\Sheets\Exceptions\ValueConversionException;
use Carbon\Carbon;

enum ColumnDataType: string
{
    case String = 'string';
    case Text = 'text';
    case Integer = 'integer';
    case BigInteger = 'bigInteger';
    case Float = 'float';
    case Decimal = 'decimal';
    case Boolean = 'boolean';
    case Date = 'date';
    case Datetime = 'datetime';
    case Json = 'json';

    /**
     * All data types accepted by the column mapping configuration.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Label shown in the administration UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::String => 'String',
            self::Text => 'Text',
            self::Integer => 'Integer',
            self::BigInteger => 'Big integer',
            self::Float => 'Float',
            self::Decimal => 'Decimal',
            self::Boolean => 'Boolean',
            self::Date => 'Date',
            self::Datetime => 'Date & time',
            self::Json => 'JSON',
        };
    }

    /**
     * Schema builder method used when a destination column is created.
     */
    public function schemaType(): string
    {
        return match ($this) {
            self::String => 'string',
            self::Text => 'text',
            self::Integer => 'integer',
            self::BigInteger => 'bigInteger',
            self::Float => 'float',
            self::Decimal => 'decimal',
            self::Boolean => 'boolean',
            self::Date => 'date',
            self::Datetime => 'dateTime',
            self::Json => 'json',
        };
    }

    /**
     * Convert a raw spreadsheet cell into a value that can be persisted
     * in the destination column.
     *
     * @throws ValueConversionException When the value cannot be converted.
     */
    public function castValue(?string $value): int|float|bool|string|null
    {
        $value = $value === null ? null : trim($value);

        if ($value === null || $value === '') {
            return null;
        }

        return match ($this) {
            self::String, self::Text => $value,
            self::Integer, self::BigInteger => $this->castInteger($value),
            self::Float, self::Decimal => $this->castDecimal($value),
            self::Boolean => $this->castBoolean($value),
            self::Date => $this->castDate($value),
            self::Datetime => $this->castDatetime($value),
            self::Json => $this->castJson($value),
        };
    }

    private function castInteger(string $value): int
    {
        $normalized = str_replace([',', ' '], '', $value);

        if (! preg_match('/^-?\d+$/', $normalized)) {
            throw new ValueConversionException("[$value] is not a valid integer.");
        }

        return (int) $normalized;
    }

    private function castDecimal(string $value): float|string
    {
        $normalized = str_replace([',', ' '], '', $value);

        if (! is_numeric($normalized)) {
            throw new ValueConversionException("[$value] is not a valid number.");
        }

        return $this === self::Decimal ? $normalized : (float) $normalized;
    }

    private function castBoolean(string $value): bool
    {
        $normalized = mb_strtolower($value);

        return match (true) {
            in_array($normalized, ['1', 'true', 'yes', 'y', 'on'], true) => true,
            in_array($normalized, ['0', 'false', 'no', 'n', 'off'], true) => false,
            default => throw new ValueConversionException("[$value] is not a valid boolean."),
        };
    }

    private function castDate(string $value): string
    {
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            throw new ValueConversionException("[$value] is not a valid date.");
        }
    }

    private function castDatetime(string $value): string
    {
        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            throw new ValueConversionException("[$value] is not a valid date and time.");
        }
    }

    private function castJson(string $value): array|string
    {
        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ValueConversionException("[$value] is not valid JSON.");
        }

        return $decoded;
    }
}
