<?php

namespace App\Services\Sheets;

final readonly class ValidationIssue
{
    public function __construct(
        public string $level,
        public string $subject,
        public string $message,
    ) {}

    /**
     * @return array{level: string, subject: string, message: string}
     */
    public function toArray(): array
    {
        return [
            'level' => $this->level,
            'subject' => $this->subject,
            'message' => $this->message,
        ];
    }
}
