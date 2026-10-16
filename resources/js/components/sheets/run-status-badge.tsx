import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { ImportRunStatus } from '@/types';

const fallback: Record<ImportRunStatus, string> = {
    success:
        'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
    partial:
        'bg-yellow-100 text-yellow-800 dark:bg-yellow-950 dark:text-yellow-300',
    failed: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
    running: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
    pending: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
};

export function RunStatusBadge({
    status,
    label,
    className,
}: {
    status: ImportRunStatus;
    label: string;
    className?: string;
}) {
    return (
        <Badge
            variant="outline"
            className={cn('border-transparent', fallback[status], className)}
        >
            {label}
        </Badge>
    );
}
