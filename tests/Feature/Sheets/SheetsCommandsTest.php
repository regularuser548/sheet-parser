<?php

use App\Enums\ColumnDataType;
use App\Jobs\RunSheetImportJob;
use App\Models\SheetSource;
use App\Models\SheetTab;
use App\Services\Sheets\Contracts\SheetReader;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;

afterEach(function () {
    cleanImportSchema();
});

test('sheets:import command imports all enabled sources and records a run', function () {
    $source = SheetSource::factory()->create(['spreadsheet_id' => 'sheet-1']);
    $tab = SheetTab::factory()->create(['sheet_source_id' => $source->id, 'name' => 'Data']);
    $mapping = $tab->importMappings()->create(['table_name' => 'items', 'enabled' => true]);
    $mapping->columnMappings()->create([
        'source_column' => 'Name',
        'destination_column' => 'name',
        'data_type' => ColumnDataType::String,
        'nullable' => true,
    ]);

    $reader = fakeSheetReader();
    $reader->withData('sheet-1', 'Data', ['Name'], [['Widget']]);
    $this->app->instance(SheetReader::class, $reader);

    $exit = Artisan::call('sheets:import');

    expect($exit)->toBe(0);
    expect(Artisan::output())->toContain($source->name);
    $this->assertDatabaseHas('items', ['name' => 'Widget']);
});

test('sheets:import command accepts a source identifier', function () {
    $source = SheetSource::factory()->create(['spreadsheet_id' => 'sheet-1']);
    $tab = SheetTab::factory()->create(['sheet_source_id' => $source->id, 'name' => 'Data']);
    $mapping = $tab->importMappings()->create(['table_name' => 'items', 'enabled' => true]);
    $mapping->columnMappings()->create([
        'source_column' => 'Name',
        'destination_column' => 'name',
        'data_type' => ColumnDataType::String,
        'nullable' => true,
    ]);

    $reader = fakeSheetReader();
    $reader->withData('sheet-1', 'Data', ['Name'], [['Widget']]);
    $this->app->instance(SheetReader::class, $reader);

    $exit = Artisan::call('sheets:import', ['source' => $source->id]);

    expect($exit)->toBe(0);
});

test('sheets:import --queue dispatches the import job', function () {
    Queue::fake();
    $source = SheetSource::factory()->create();

    $exit = Artisan::call('sheets:import', ['--queue' => true]);

    expect($exit)->toBe(0);
    Queue::assertPushed(RunSheetImportJob::class);
});

test('sheets:validate command outputs the validation report', function () {
    $source = SheetSource::factory()->create(['name' => 'Example']);
    $tab = SheetTab::factory()->create(['sheet_source_id' => $source->id, 'name' => 'Data']);
    $mapping = $tab->importMappings()->create(['table_name' => 'items', 'enabled' => true]);
    $mapping->columnMappings()->create([
        'source_column' => 'Name',
        'destination_column' => 'name',
        'data_type' => ColumnDataType::String,
        'nullable' => true,
    ]);

    $reader = fakeSheetReader();
    $reader->withData($source->spreadsheet_id, 'Data', ['Name'], [['Alice']]);
    $this->app->instance(SheetReader::class, $reader);

    $exit = Artisan::call('sheets:validate');

    expect($exit)->toBe(0);
    expect(Artisan::output())->toContain('Validation finished');
});
