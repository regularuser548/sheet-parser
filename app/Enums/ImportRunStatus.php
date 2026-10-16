<?php

namespace App\Enums;

enum ImportRunStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Success = 'success';
    case Partial = 'partial';
    case Failed = 'failed';

    /**
     * Human-readable label for the administration UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Running => 'Running',
            self::Success => 'Success',
            self::Partial => 'Partial',
            self::Failed => 'Failed',
        };
    }

    /**
     * Tailwind badge classes used by the import history table.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Success => 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
            self::Partial => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950 dark:text-yellow-300',
            self::Failed => 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
            self::Running, self::Pending => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
        };
    }
}
