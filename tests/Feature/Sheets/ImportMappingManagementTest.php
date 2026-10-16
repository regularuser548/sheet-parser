<?php

use App\Models\ImportMapping;
use App\Models\SheetSource;
use App\Models\SheetTab;
use App\Models\User;

test('a mapping with columns can be created for a tab', function () {
    $tab = SheetTab::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('sheets.mappings.store', $tab), [
            'table_name' => 'sales_records',
            'enabled' => true,
            'columns' => [
                sheetImportColumn(),
                sheetImportColumn(['source_column' => 'Total', 'destination_column' => 'total', 'data_type' => 'decimal']),
            ],
        ])
        ->assertRedirect(route('sheets.mappings.index', $tab));

    $mapping = ImportMapping::query()->where('table_name', 'sales_records')->firstOrFail();
    expect($mapping->columnMappings)->toHaveCount(2);
});

test('mapping columns reject duplicate source and destination names', function () {
    $tab = SheetTab::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('sheets.mappings.store', $tab), [
            'table_name' => 'sales_records',
            'columns' => [
                sheetImportColumn(),
                sheetImportColumn(['destination_column' => 'name']),
            ],
        ])
        ->assertSessionHasErrors(['columns.1.destination_column']);

    $this->assertDatabaseCount('import_mappings', 0);
});

test('mapping rejects protected destination tables', function () {
    $tab = SheetTab::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('sheets.mappings.store', $tab), [
            'table_name' => 'users',
            'columns' => [sheetImportColumn()],
        ])
        ->assertSessionHasErrors('table_name');
});

test('mapping rejects a default value that cannot be converted', function () {
    $tab = SheetTab::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('sheets.mappings.store', $tab), [
            'table_name' => 'sales_records',
            'columns' => [
                sheetImportColumn(['data_type' => 'integer', 'default_value' => 'not-a-number']),
            ],
        ])
        ->assertSessionHasErrors('columns.0.default_value');
});

test('a mapping can be updated and its columns replaced', function () {
    $source = SheetSource::factory()->create();
    $tab = SheetTab::factory()->create(['sheet_source_id' => $source->id]);
    $mapping = ImportMapping::factory()->create(['sheet_tab_id' => $tab->id, 'table_name' => 'sales_records']);
    $mapping->columnMappings()->create(sheetImportColumn(['data_type' => 'string']));

    $this->actingAs(User::factory()->create())
        ->put(route('sheets.mappings.update', $mapping), [
            'table_name' => 'sales_records',
            'enabled' => true,
            'columns' => [
                sheetImportColumn(['source_column' => 'Amount', 'destination_column' => 'amount', 'data_type' => 'integer']),
            ],
        ])
        ->assertRedirect(route('sheets.mappings.index', $tab));

    $mapping->refresh()->load('columnMappings');
    expect($mapping->columnMappings)->toHaveCount(1);
    expect($mapping->columnMappings->first()->destination_column)->toBe('amount');
});

test('a mapping can be deleted', function () {
    $mapping = ImportMapping::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('sheets.mappings.destroy', $mapping))
        ->assertRedirect(route('sheets.mappings.index', $mapping->sheet_tab_id));

    $this->assertDatabaseMissing('import_mappings', ['id' => $mapping->id]);
});
