<?php

use App\Enums\ColumnDataType;
use App\Models\ColumnMapping;
use App\Models\ImportMapping;
use App\Models\SheetSource;
use App\Models\SheetTab;
use App\Services\Sheets\ConfigurationValidator;
use App\Services\Sheets\Contracts\SheetReader;
use App\Services\Sheets\DestinationSchemaManager;
use App\Services\Sheets\Exceptions\UnsafeIdentifierException;
use Illuminate\Support\Facades\Schema;

afterEach(function () {
    cleanImportSchema();
});

test('configuration validator reports missing configured columns when fetching headers', function () {
    $source = SheetSource::factory()->create(['spreadsheet_id' => 'sheet-1', 'name' => 'Source']);
    $tab = SheetTab::factory()->create(['sheet_source_id' => $source->id, 'name' => 'Data']);
    $mapping = $tab->importMappings()->create(['table_name' => 'reports', 'enabled' => true]);
    $mapping->columnMappings()->create([
        'source_column' => 'Name',
        'destination_column' => 'name',
        'data_type' => ColumnDataType::String,
        'nullable' => true,
    ]);
    $mapping->columnMappings()->create([
        'source_column' => 'Total',
        'destination_column' => 'total',
        'data_type' => ColumnDataType::Decimal,
        'nullable' => true,
    ]);

    $reader = fakeSheetReader();
    $reader->withData('sheet-1', 'Data', ['Name'], [['Alice']]);
    $this->app->instance(SheetReader::class, $reader);

    $validator = app(ConfigurationValidator::class);
    $issues = $validator->validateSource($source);

    expect(collect($issues)->pluck('message')->implode(' '))
        ->toContain('were not found');
});

test('configuration validator detects unsafe table identifiers', function () {
    $source = SheetSource::factory()->create();
    $tab = SheetTab::factory()->create(['sheet_source_id' => $source->id]);
    $mapping = $tab->importMappings()->create(['table_name' => 'users', 'enabled' => true]);
    $mapping->columnMappings()->create([
        'source_column' => 'Name',
        'destination_column' => 'name',
        'data_type' => ColumnDataType::String,
        'nullable' => true,
    ]);

    $reader = fakeSheetReader();
    $this->app->instance(SheetReader::class, $reader);

    $validator = app(ConfigurationValidator::class);
    $issues = $validator->validateSource($source, fetch: false);

    expect(collect($issues)->pluck('message')->implode(' '))
        ->toContain('protected');
});

test('destination schema manager plans missing columns and creates them', function () {
    $manager = app(DestinationSchemaManager::class);

    $mapping = ImportMapping::factory()->create(['table_name' => 'temp_items']);
    $columns = [
        ColumnMapping::factory()->for($mapping, 'mapping')->make([
            'destination_column' => 'name',
            'data_type' => ColumnDataType::String,
            'nullable' => true,
        ]),
        ColumnMapping::factory()->for($mapping, 'mapping')->make([
            'destination_column' => 'value',
            'data_type' => ColumnDataType::Integer,
            'nullable' => true,
        ]),
    ];

    $plan = $manager->plan('temp_items', collect($columns)->keyBy('destination_column')->all());
    expect($plan->tableExists)->toBeFalse();
    expect($plan->missingColumns)->toContain('name');

    $manager->ensureDestination('temp_items', collect($columns)->keyBy('destination_column')->all());
    expect(Schema::hasTable('temp_items'))->toBeTrue();
    expect(Schema::hasColumn('temp_items', 'name'))->toBeTrue();
    expect(Schema::hasColumn('temp_items', 'value'))->toBeTrue();
});

test('destination schema manager rejects unsafe identifiers and protected tables', function () {
    $manager = app(DestinationSchemaManager::class);

    expect(fn () => $manager->assertSafeTable('users'))->toThrow(UnsafeIdentifierException::class);
    expect(fn () => $manager->assertSafeTable('drop table users'))->toThrow(UnsafeIdentifierException::class);
    expect(fn () => $manager->assertSafeColumn('id'))->toThrow(UnsafeIdentifierException::class);
    expect(DestinationSchemaManager::isProtectedTable('users'))->toBeTrue();
    expect(DestinationSchemaManager::isValidIdentifier('valid_column_1'))->toBeTrue();
});
