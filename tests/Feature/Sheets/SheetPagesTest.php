<?php

use App\Models\ImportMapping;
use App\Models\ImportRun;
use App\Models\SheetSource;
use App\Models\SheetTab;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the create source page renders', function () {
    $this->get(route('sheets.sources.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('sheets/sources/create'));
});

test('the edit source page renders tabs as an array', function () {
    $source = SheetSource::factory()->create();
    SheetTab::factory()->count(2)->create(['sheet_source_id' => $source->id]);

    $this->get(route('sheets.sources.edit', $source))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('sheets/sources/edit')
            ->where('source.name', $source->name)
            ->has('source.tabs', 2));
});

test('the mappings page renders mappings as an array', function () {
    $tab = SheetTab::factory()->create();
    $mapping = ImportMapping::factory()->create(['sheet_tab_id' => $tab->id]);
    $mapping->columnMappings()->create([
        'source_column' => 'Name',
        'destination_column' => 'name',
        'data_type' => 'string',
        'nullable' => true,
    ]);

    $this->get(route('sheets.mappings.index', $tab))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('sheets/mappings/index')
            ->has('dataTypes')
            ->has('mappings', 1)
            ->has('mappings.0.columns', 1));
});

test('the import history page renders runs and sources as arrays', function () {
    $source = SheetSource::factory()->create();
    ImportRun::factory()->count(3)->create([
        'sheet_source_id' => $source->id,
    ]);

    $this->get(route('sheets.runs.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('sheets/runs/index')
            ->has('runs.data', 3)
            ->has('sources', 1));
});

test('the import history page can filter by source', function () {
    $source = SheetSource::factory()->create();
    $other = SheetSource::factory()->create();
    ImportRun::factory()->create(['sheet_source_id' => $source->id]);
    ImportRun::factory()->create(['sheet_source_id' => $other->id]);

    $this->get(route('sheets.runs.index', ['source' => $source->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('runs.data', 1)
            ->where('filters.source', $source->id));
});
