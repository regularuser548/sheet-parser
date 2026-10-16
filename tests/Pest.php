<?php

use App\Services\Sheets\Contracts\SheetReader;
use App\Services\Sheets\DestinationSchemaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\FakeSheetReader;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
 * Bind an in-memory {@see SheetReader} for the current test and return it.
 *
 * Import tests use this instead of the Google Sheets API so no credentials
 * or network access are required.
 */
function fakeSheetReader(): FakeSheetReader
{
    $reader = new FakeSheetReader;

    app()->instance(SheetReader::class, $reader);

    return $reader;
}

/*
 * Build a single import mapping column payload for HTTP form tests.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function sheetImportColumn(array $overrides = []): array
{
    return array_merge([
        'source_column' => 'Name',
        'destination_column' => 'name',
        'data_type' => 'string',
        'nullable' => true,
    ], $overrides);
}

/*
 * Remove the state that an import test commits: any destination table the
 * importer created, and the configuration and history rows it wrote.
 *
 * This is much cheaper than rebuilding the schema after every test and keeps
 * DDL-based tests isolated from the rest of the suite.
 */
function cleanImportSchema(): void
{
    $protected = DestinationSchemaManager::PROTECTED_TABLES;

    $tables = collect(Schema::getTables())->pluck('name');

    $tables->reject(fn (string $table): bool => in_array($table, $protected, true))
        ->each(fn (string $table) => Schema::dropIfExists($table));

    foreach (['sheet_sources', 'sheet_tabs', 'import_mappings', 'column_mappings', 'import_runs', 'import_row_markers'] as $table) {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table($table)->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
