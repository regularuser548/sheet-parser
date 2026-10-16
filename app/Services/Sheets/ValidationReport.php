<?php

namespace App\Services\Sheets;

final readonly class ValidationReport
{
    /**
     * @param  list<ValidationIssue>  $issues
     */
    public function __construct(public array $issues = []) {}

    /**
     * @return list<ValidationIssue>
     */
    public function errors(): array
    {
        return array_values(array_filter($this->issues, fn (ValidationIssue $issue): bool => $issue->level === 'error'));
    }

    /**
     * @return list<ValidationIssue>
     */
    public function warnings(): array
    {
        return array_values(array_filter($this->issues, fn (ValidationIssue $issue): bool => $issue->level === 'warning'));
    }

    public function hasErrors(): bool
    {
        return $this->errors() !== [];
    }

    /**
     * @return array{valid: bool, errors: int, warnings: int, issues: list<array{level: string, subject: string, message: string}>}
     */
    public function toArray(): array
    {
        return [
            'valid' => ! $this->hasErrors(),
            'errors' => count($this->errors()),
            'warnings' => count($this->warnings()),
            'issues' => array_map(fn (ValidationIssue $issue): array => $issue->toArray(), $this->issues),
        ];
    }
}
