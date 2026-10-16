<?php

namespace App\Services\Sheets;

final readonly class SchemaPlan
{
    /**
     * @param  list<string>  $presentColumns
     * @param  list<string>  $missingColumns
     */
    public function __construct(
        public string $table,
        public bool $tableExists,
        public array $presentColumns,
        public array $missingColumns,
    ) {}

    /**
     * Whether the destination matches the configured mapping already.
     */
    public function isSatisfied(): bool
    {
        return $this->tableExists && $this->missingColumns === [];
    }

    /**
     * Whether applying this plan requires a schema change.
     */
    public function requiresChange(): bool
    {
        return ! $this->isSatisfied();
    }
}
