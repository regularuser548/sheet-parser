<?php

use App\Enums\ColumnDataType;
use App\Services\Sheets\Exceptions\ValueConversionException;

test('casts strings and text as-is', function () {
    expect(ColumnDataType::String->castValue(' hello '))->toBe('hello');
    expect(ColumnDataType::Text->castValue('world'))->toBe('world');
    expect(ColumnDataType::String->castValue(null))->toBeNull();
    expect(ColumnDataType::String->castValue(''))->toBeNull();
});

test('casts integers and big integers', function () {
    expect(ColumnDataType::Integer->castValue('10'))->toBe(10);
    expect(ColumnDataType::Integer->castValue('-5'))->toBe(-5);
    expect(ColumnDataType::Integer->castValue('1,234'))->toBe(1234);
    expect(ColumnDataType::BigInteger->castValue('  42  '))->toBe(42);
});

test('throws on invalid integer', function () {
    ColumnDataType::Integer->castValue('abc');
})->throws(ValueConversionException::class);

test('casts decimals', function () {
    expect(ColumnDataType::Float->castValue('10.5'))->toBe(10.5);
    expect(ColumnDataType::Decimal->castValue('10.50'))->toBe('10.50');
    expect(ColumnDataType::Decimal->castValue('1,000.25'))->toBe('1000.25');
});

test('throws on invalid decimal', function () {
    ColumnDataType::Float->castValue('abc');
})->throws(ValueConversionException::class);

test('casts booleans', function () {
    expect(ColumnDataType::Boolean->castValue('true'))->toBeTrue();
    expect(ColumnDataType::Boolean->castValue('yes'))->toBeTrue();
    expect(ColumnDataType::Boolean->castValue('1'))->toBeTrue();
    expect(ColumnDataType::Boolean->castValue('false'))->toBeFalse();
    expect(ColumnDataType::Boolean->castValue('no'))->toBeFalse();
    expect(ColumnDataType::Boolean->castValue('0'))->toBeFalse();
});

test('throws on invalid boolean', function () {
    ColumnDataType::Boolean->castValue('maybe');
})->throws(ValueConversionException::class);

test('casts dates and datetimes', function () {
    expect(ColumnDataType::Date->castValue('2024-01-02'))->toBe('2024-01-02');
    expect(ColumnDataType::Datetime->castValue('2024-01-02 15:30:45'))->toBe('2024-01-02 15:30:45');
});

test('throws on invalid dates', function () {
    ColumnDataType::Date->castValue('not-a-date');
})->throws(ValueConversionException::class);

test('validates json and returns the original string', function () {
    expect(ColumnDataType::Json->castValue('{"a":1}'))->toBe('{"a":1}');
    expect(ColumnDataType::Json->castValue('[1, 2, 3]'))->toBe('[1, 2, 3]');
});

test('throws on invalid json', function () {
    ColumnDataType::Json->castValue('{bad}');
})->throws(ValueConversionException::class);
