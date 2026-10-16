<?php

use App\Models\SheetSource;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests cannot manage sheet sources', function () {
    $this->get(route('sheets.sources.index'))->assertRedirect(route('login'));
    $this->post(route('sheets.sources.store'), [])->assertRedirect(route('login'));
});

test('authenticated users can list sheet sources', function () {
    SheetSource::factory()->count(2)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('sheets.sources.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('sheets/sources/index')
            ->has('sources', 2));
});

test('a sheet source can be created', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('sheets.sources.store'), [
            'name' => 'Sales',
            'spreadsheet_id' => '1Abcdefghijklmnopqrstuvwxyz',
            'enabled' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('sheet_sources', [
        'name' => 'Sales',
        'spreadsheet_id' => '1Abcdefghijklmnopqrstuvwxyz',
    ]);
});

test('creating a sheet source validates the spreadsheet id', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('sheets.sources.store'), [
            'name' => 'Sales',
            'spreadsheet_id' => 'short',
        ])
        ->assertSessionHasErrors('spreadsheet_id');

    $this->assertDatabaseCount('sheet_sources', 0);
});

test('spreadsheet ids must be unique', function () {
    SheetSource::factory()->create(['spreadsheet_id' => '1Abcdefghijklmnopqrstuvwxyz']);

    $this->actingAs(User::factory()->create())
        ->post(route('sheets.sources.store'), [
            'name' => 'Duplicate',
            'spreadsheet_id' => '1Abcdefghijklmnopqrstuvwxyz',
        ])
        ->assertSessionHasErrors('spreadsheet_id');
});

test('a sheet source can be updated', function () {
    $source = SheetSource::factory()->create(['name' => 'Old']);

    $this->actingAs(User::factory()->create())
        ->put(route('sheets.sources.update', $source), [
            'name' => 'New',
            'spreadsheet_id' => $source->spreadsheet_id,
            'enabled' => false,
        ])
        ->assertRedirect();

    $source->refresh();
    expect($source->name)->toBe('New');
    expect($source->enabled)->toBeFalse();
});

test('a sheet source can be deleted', function () {
    $source = SheetSource::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('sheets.sources.destroy', $source))
        ->assertRedirect(route('sheets.sources.index'));

    $this->assertDatabaseMissing('sheet_sources', ['id' => $source->id]);
});
