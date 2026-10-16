import { AlertCircle, AlertTriangle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import type { ValidationIssue } from '@/types';

export function ValidationIssues({
    issues,
    className,
    emptyMessage = 'No issues found.',
}: {
    issues: ValidationIssue[];
    className?: string;
    emptyMessage?: string;
}) {
    if (issues.length === 0) {
        return <p className="text-sm text-muted-foreground">{emptyMessage}</p>;
    }

    return (
        <ul className={className}>
            {issues.map((issue, index) => (
                <li
                    key={`${issue.subject}-${index}`}
                    className="flex items-start gap-2 border-b border-sidebar-border/60 py-2 text-sm last:border-b-0"
                >
                    {issue.level === 'error' ? (
                        <AlertCircle className="mt-0.5 size-4 shrink-0 text-red-600 dark:text-red-400" />
                    ) : (
                        <AlertTriangle className="mt-0.5 size-4 shrink-0 text-yellow-600 dark:text-yellow-400" />
                    )}
                    <div className="space-y-0.5">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="font-medium">{issue.subject}</span>
                            <Badge
                                variant="outline"
                                className="border-transparent bg-muted text-muted-foreground"
                            >
                                {issue.level}
                            </Badge>
                        </div>
                        <p className="text-muted-foreground">{issue.message}</p>
                    </div>
                </li>
            ))}
        </ul>
    );
}
