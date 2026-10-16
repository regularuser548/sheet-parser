<?php

namespace App\Services\Sheets;

use App\Models\ColumnMapping;
use App\Services\Sheets\Exceptions\UnsafeIdentifierException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Creates and alters only the destination tables that are explicitly
 * configured through import mappings.
 *
 * Application tables and any identifier that could be used for SQL injection
 * are rejected before a schema statement is issued.
 */
class DestinationSchemaManager
{
    /**
     * Tables that must never be used as an import destination.
     *
     * @var list<string>
     */
    public const PROTECTED_TABLES = [
        'migrations',
        'users',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
        'passkeys',
        'sheet_sources',
        'sheet_tabs',
        'import_mappings',
        'column_mappings',
        'import_runs',
        'import_row_markers',
        'sqlite_sequence',
    ];

    /**
     * Maximum identifier length accepted by MySQL.
     */
    public const MAX_IDENTIFIER_LENGTH = 64;

    /**
     * Build the schema plan for a destination table without changing anything.
     *
     * @param  array<string, ColumnMapping>  $columns  Keyed by destination column name.
     */
    public function plan(string $table, array $columns): SchemaPlan
    {
        $this->assertSafeTable($table);

        foreach (array_keys($columns) as $column) {
            $this->assertSafeColumn($column);
        }

        if (! $this->tableExists($table)) {
            return new SchemaPlan($table, false, [], array_keys($columns));
        }

        $existing = $this->columnNames($table);
        $missing = array_values(array_diff(array_keys($columns), $existing));

        return new SchemaPlan($table, true, $existing, $missing);
    }

    /**
     * Ensure the destination table and every configured column exist,
     * creating or altering the table when required.
     *
     * @param  array<string, ColumnMapping>  $columns  Keyed by destination column name.
     */
    public function ensureDestination(string $table, array $columns): SchemaPlan
    {
        $plan = $this->plan($table, $columns);

        if (! $plan->requiresChange()) {
            return $plan;
        }

        if (! $plan->tableExists) {
            Schema::create($table, function (Blueprint $blueprint) use ($columns): void {
                $blueprint->id();

                foreach ($columns as $column) {
                    $this->addColumn($blueprint, $column);
                }
            });

            Log::info('Import destination table created.', [
                'table' => $table,
                'columns' => array_keys($columns),
            ]);

            return $plan;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $plan): void {
            foreach ($plan->missingColumns as $column) {
                $this->addColumn($blueprint, $columns[$column]);
            }
        });

        Log::info('Import destination columns created.', [
            'table' => $table,
            'columns' => $plan->missingColumns,
        ]);

        return $plan;
    }

    /**
     * Whether a table already exists in the database.
     */
    public function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    /**
     * Column names of an existing table.
     *
     * @return list<string>
     */
    public function columnNames(string $table): array
    {
        return array_map(
            static fn (array $column): string => $column['name'],
            Schema::getColumns($table),
        );
    }

    /**
     * Return the auto-incrementing primary key of a table, if it has one.
     */
    public function autoIncrementingPrimaryKey(string $table): ?string
    {
        if (! $this->tableExists($table)) {
            return null;
        }

        $columns = collect(Schema::getColumns($table))->keyBy('name');

        foreach (Schema::getIndexes($table) as $index) {
            if (! $index['primary'] || count($index['columns']) !== 1) {
                continue;
            }

            $column = $index['columns'][0];

            if ($columns->get($column)['auto_increment'] ?? false) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Validate a destination table identifier.
     */
    public function assertSafeTable(string $table): void
    {
        if (! self::isValidIdentifier($table)) {
            throw new UnsafeIdentifierException("[$table] is not a valid table name.");
        }

        if (self::isProtectedTable($table)) {
            throw new UnsafeIdentifierException("The [$table] table is protected and cannot be used as an import destination.");
        }
    }

    /**
     * Validate a destination column identifier.
     */
    public function assertSafeColumn(string $column): void
    {
        if (! self::isValidIdentifier($column)) {
            throw new UnsafeIdentifierException("[$column] is not a valid column name.");
        }

        if (mb_strtolower($column) === 'id') {
            throw new UnsafeIdentifierException('The primary key column [id] is managed by the importer and cannot be configured.');
        }
    }

    /**
     * Whether an identifier only contains safe, unquoted characters.
     */
    public function isSafeIdentifier(string $identifier): bool
    {
        return self::isValidIdentifier($identifier);
    }

    /**
     * Whether an identifier only contains safe, unquoted characters.
     */
    public static function isValidIdentifier(string $identifier): bool
    {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier) === 1
            && mb_strlen($identifier) <= self::MAX_IDENTIFIER_LENGTH;
    }

    /**
     * Whether a table name is reserved for the application.
     */
    public static function isProtectedTable(string $table): bool
    {
        return in_array(mb_strtolower($table), self::PROTECTED_TABLES, true);
    }

    private function addColumn(Blueprint $blueprint, ColumnMapping $column): void
    {
        $definition = $blueprint
            ->{$column->data_type->schemaType()}($column->destination_column)
            ->nullable($column->nullable);

        if (! $column->nullable && $column->default_value !== null) {
            $definition->default($column->default_value);
        }
    }
}
