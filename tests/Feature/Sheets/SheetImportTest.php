<?php

use App\Enums\ColumnDataType;
use App\Enums\ImportRunStatus;
use App\Models\SheetSource;
use App\Models\SheetTab;
use App\Services\Sheets\SheetImporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

afterEach(function () {
    cleanImportSchema();
});

/**
 * @param  array<string, mixed>  $attributes
 */
function salesSource(array $attributes = []): SheetSource
{
    return SheetSource::factory()->create([
        'spreadsheet_id' => 'spreadsheet-sales',
        ...$attributes,
    ]);
}

test('imports rows using the configured header positions', function () {
    $source = salesSource();
    $tab = SheetTab::factory()->create(['sheet_source_id' => $source->id, 'name' => 'Sales']);
    $mapping = $tab->importMappings()->create(['table_name' => 'sales', 'enabled' => true]);

    $mapping->columnMappings()->create([
        'source_column' => 'Product Name',
        'destination_column' => 'name',
        'data_type' => ColumnDataType::String,
        'nullable' => true,
    ]);
    $mapping->columnMappings()->create([
        'source_column' => 'Date',
        'destination_column' => 'sold_on',
        'data_type' => ColumnDataType::Date,
        'nullable' => true,
    ]);
    $mapping->columnMappings()->create([
        'source_column' => 'Amount',
        'destination_column' => 'amount',
        'data_type' => ColumnDataType::Integer,
        'nullable' => true,
    ]);

    fakeSheetReader()->withData('spreadsheet-sales', 'Sales', ['Amount', 'Date', 'Product Name'], [
        ['10', '2024-01-02', 'Widget'],
        ['5', '2024-02-03', 'Gadget'],
    ]);

    $run = app(SheetImporter::class)->import($source);

    expect($run->status)->toBe(ImportRunStatus::Success);
    expect($run->imported_rows)->toBe(2);

    $this->assertDatabaseCount('sales', 2);
    $this->assertDatabaseHas('sales', [
        'name' => 'Widget',
        'sold_on' => '2024-01-02',
        'amount' => 10,
    ]);
    $this->assertDatabaseHas('sales', [
        'name' => 'Gadget',
        'sold_on' => '2024-02-03',
        'amount' => 5,
    ]);
});

test('ignores source columns that are not mapped', function () {
    $source = salesSource();
    $tab = SheetTab::factory()->create(['sheet_source_id' => $source->id, 'name' => 'Sales']);
    $mapping = $tab->importMappings()->create(['table_name' => 'sales', 'enabled' => true]);
    $mapping->columnMappings()->create([
        'source_column' => 'Product Name',
        'destination_column' => 'name',
        'data_type' => ColumnDataType::String,
        'nullable' => true,
    ]);

    fakeSheetReader()->withData('spreadsheet-sales', 'Sales', ['Product Name', 'Internal Notes'], [
        ['Widget', 'do not import'],
    ]);

    $run = app(SheetImporter::class)->import($source);

    expect($run->status)->toBe(ImportRunStatus::Success);

    expect(Schema::getColumnListing('sales'))->not->toContain('internal_notes');
    $this->assertDatabaseHas('sales', ['name' => 'Widget']);
});

test('reports an error when a configured source column is missing', function () {
    $source = salesSource();
    $tab = SheetTab::factory()->create(['sheet_source_id' => $source->id, 'name' => 'Sales']);
    $mapping = $tab->importMappings()->create(['table_name' => 'sales', 'enabled' => true]);
    $mapping->columnMappings()->create([
        'source_column' => 'Missing Column',
        'destination_column' => 'name',
        'data_type' => ColumnDataType::String,
        'nullable' => true,
    ]);

    fakeSheetReader()->withData('spreadsheet-sales', 'Sales', ['Product Name'], [
        ['Widget'],
    ]);

    $run = app(SheetImporter::class)->import($source);

    expect($run->status)->toBe(ImportRunStatus::Failed);
    expect($run->issues())->toHaveCount(2);

    expect(collect($run->issues())->pluck('message')->implode(' '))
        ->toContain('Missing Column');
});

test('writes one tab to multiple destination tables', function () {
    $source = salesSource();
    $tab = SheetTab::factory()->create(['sheet_source_id' => $source->id, 'name' => 'Sales']);

    $sales = $tab->importMappings()->create(['table_name' => 'sales', 'enabled' => true]);
    $sales->columnMappings()->create([
        'source_column' => 'Product Name',
        'destination_column' => 'name',
        'data_type' => ColumnDataType::String,
        'nullable' => true,
    ]);
    $sales->columnMappings()->create([
        'source_column' => 'Amount',
        'destination_column' => 'amount',
        'data_type' => ColumnDataType::Integer,
        'nullable' => true,
    ]);

    $lines = $tab->importMappings()->create(['table_name' => 'sales_lines', 'enabled' => true]);
    $lines->columnMappings()->create([
        'source_column' => 'Product Name',
        'destination_column' => 'description',
        'data_type' => ColumnDataType::String,
        'nullable' => true,
    ]);

    fakeSheetReader()->withData('spreadsheet-sales', 'Sales', ['Product Name', 'Amount'], [
        ['Widget', '10'],
    ]);

    $run = app(SheetImporter::class)->import($source);

    expect($run->status)->toBe(ImportRunStatus::Success);

    $this->assertDatabaseHas('sales', ['name' => 'Widget', 'amount' => 10]);
    $this->assertDatabaseHas('sales_lines', ['description' => 'Widget']);
});

test('adds a missing column to an existing destination table', function () {
    Schema::create('sales', function ($table) {
        $table->id();
        $table->string('name')->nullable();
    });

    $source = salesSource();
    $tab = SheetTab::factory()->create(['sheet_source_id' => $source->id, 'name' => 'Sales']);
    $mapping = $tab->importMappings()->create(['table_name' => 'sales', 'enabled' => true]);
    $mapping->columnMappings()->create([
        'source_column' => 'Product Name',
        'destination_column' => 'name',
        'data_type' => ColumnDataType::String,
        'nullable' => true,
    ]);
    $mapping->columnMappings()->create([
        'source_column' => 'Amount',
        'destination_column' => 'amount',
        'data_type' => ColumnDataType::Integer,
        'nullable' => true,
    ]);

    fakeSheetReader()->withData('spreadsheet-sales', 'Sales', ['Product Name', 'Amount'], [
        ['Widget', '10'],
    ]);

    $run = app(SheetImporter::class)->import($source);

    expect($run->status)->toBe(ImportRunStatus::Success);
    expect(Schema::hasColumn('sales', 'amount'))->toBeTrue();

    $this->assertDatabaseHas('sales', ['name' => 'Widget', 'amount' => 10]);
});

test('converts values according to the configured data types', function () {
    $source = salesSource();
    $tab = SheetTab::factory()->create(['sheet_source_id' => $source->id, 'name' => 'Sales']);
    $mapping = $tab->importMappings()->create(['table_name' => 'sales', 'enabled' => true]);

    $mapping->columnMappings()->create([
        'source_column' => 'Amount',
        'destination_column' => 'amount',
        'data_type' => ColumnDataType::Integer,
        'nullable' => true,
    ]);
    $mapping->columnMappings()->create([
        'source_column' => 'Active',
        'destination_column' => 'active',
        'data_type' => ColumnDataType::Boolean,
        'nullable' => true,
    ]);
    $mapping->columnMappings()->create([
        'source_column' => 'Metadata',
        'destination_column' => 'meta',
        'data_type' => ColumnDataType::Json,
        'nullable' => true,
    ]);

    fakeSheetReader()->withData('spreadsheet-sales', 'Sales', ['Amount', 'Active', 'Metadata'], [
        ['1 234', 'yes', '{"color":"red"}'],
    ]);

    $run = app(SheetImporter::class)->import($source);

    expect($run->status)->toBe(ImportRunStatus::Success);

    $this->assertDatabaseHas('sales', [
        'amount' => 1234,
        'active' => true,
    ]);
    expect(json_decode((string) DB::table('sales')->value('meta'), true))->toBe(['color' => 'red']);
});

test('does not duplicate rows on a repeated import and updates changed rows', function () {
    $source = salesSource();
    $tab = SheetTab::factory()->create(['sheet_source_id' => $source->id, 'name' => 'Sales']);
    $mapping = $tab->importMappings()->create(['table_name' => 'sales', 'enabled' => true]);
    $mapping->columnMappings()->create([
        'source_column' => 'Product Name',
        'destination_column' => 'name',
        'data_type' => ColumnDataType::String,
        'nullable' => true,
    ]);
    $mapping->columnMappings()->create([
        'source_column' => 'Amount',
        'destination_column' => 'amount',
        'data_type' => ColumnDataType::Integer,
        'nullable' => true,
    ]);

    $reader = fakeSheetReader();
    $reader->withData('spreadsheet-sales', 'Sales', ['Product Name', 'Amount'], [
        ['Widget', '10'],
    ]);

    $importer = app(SheetImporter::class);
    $importer->import($source);

    $this->assertDatabaseCount('sales', 1);

    $secondRun = $importer->import($source);

    expect($secondRun->imported_rows)->toBe(0);
    $this->assertDatabaseCount('sales', 1);

    $reader->withData('spreadsheet-sales', 'Sales', ['Product Name', 'Amount'], [
        ['Widget', '25'],
    ]);

    $thirdRun = $importer->import($source);

    expect($thirdRun->updated_rows)->toBe(1);
    $this->assertDatabaseCount('sales', 1);
    $this->assertDatabaseHas('sales', ['name' => 'Widget', 'amount' => 25]);
});

test('reports a failure when a tab cannot be read', function () {
    $source = salesSource();
    $tab = SheetTab::factory()->create(['sheet_source_id' => $source->id, 'name' => 'Sales']);
    $mapping = $tab->importMappings()->create(['table_name' => 'sales', 'enabled' => true]);
    $mapping->columnMappings()->create([
        'source_column' => 'Product Name',
        'destination_column' => 'name',
        'data_type' => ColumnDataType::String,
        'nullable' => true,
    ]);

    fakeSheetReader()->failWith('spreadsheet-sales', 'Sales', 'Access denied.');

    $run = app(SheetImporter::class)->import($source);

    expect($run->status)->toBe(ImportRunStatus::Failed);
    expect(collect($run->issues())->pluck('message')->implode(' '))->toContain('Access denied.');
});

test('imports other sources when one source fails', function () {
    $failing = SheetSource::factory()->create([
        'spreadsheet_id' => 'spreadsheet-broken',
        'name' => 'Broken',
    ]);
    $failingTab = SheetTab::factory()->create(['sheet_source_id' => $failing->id, 'name' => 'Data']);
    $failingMapping = $failingTab->importMappings()->create(['table_name' => 'broken', 'enabled' => true]);
    $failingMapping->columnMappings()->create([
        'source_column' => 'Name',
        'destination_column' => 'name',
        'data_type' => ColumnDataType::String,
        'nullable' => true,
    ]);

    $healthy = salesSource(['name' => 'Healthy']);
    $healthyTab = SheetTab::factory()->create(['sheet_source_id' => $healthy->id, 'name' => 'Sales']);
    $healthyMapping = $healthyTab->importMappings()->create(['table_name' => 'sales', 'enabled' => true]);
    $healthyMapping->columnMappings()->create([
        'source_column' => 'Product Name',
        'destination_column' => 'name',
        'data_type' => ColumnDataType::String,
        'nullable' => true,
    ]);

    $reader = fakeSheetReader();
    $reader->failWith('spreadsheet-broken', 'Data', 'Spreadsheet not found.');
    $reader->withData('spreadsheet-sales', 'Sales', ['Product Name'], [['Widget']]);

    $runs = app(SheetImporter::class)->importAll();

    expect($runs)->toHaveCount(2);
    expect($runs->firstWhere('sheet_source_id', $failing->id)->status)->toBe(ImportRunStatus::Failed);
    expect($runs->firstWhere('sheet_source_id', $healthy->id)->status)->toBe(ImportRunStatus::Success);

    $this->assertDatabaseHas('sales', ['name' => 'Widget']);
});

test('skips disabled sources and disabled tabs', function () {
    $source = SheetSource::factory()->disabled()->create(['spreadsheet_id' => 'spreadsheet-sales']);
    $tab = SheetTab::factory()->create(['sheet_source_id' => $source->id, 'name' => 'Sales']);
    $mapping = $tab->importMappings()->create(['table_name' => 'sales', 'enabled' => true]);
    $mapping->columnMappings()->create([
        'source_column' => 'Product Name',
        'destination_column' => 'name',
        'data_type' => ColumnDataType::String,
        'nullable' => true,
    ]);

    fakeSheetReader()->withData('spreadsheet-sales', 'Sales', ['Product Name'], [['Widget']]);

    $runs = app(SheetImporter::class)->importAll();

    expect($runs)->toHaveCount(0);
    expect(Schema::hasTable('sales'))->toBeFalse();
});
