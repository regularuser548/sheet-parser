import { Head, Link, router } from '@inertiajs/react';
import { ChevronDown, ChevronRight } from 'lucide-react';
import { Fragment, useState } from 'react';
import { RunStatusBadge } from '@/components/sheets/run-status-badge';
import { ValidationIssues } from '@/components/sheets/validation-issues';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import runs from '@/routes/sheets/runs';
import sources from '@/routes/sheets/sources';
import type { ImportRun, Paginated, SheetSource } from '@/types';

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString();
}

export default function RunsIndex({
    runs: paginator,
    sources: sourceList,
    filters,
}: {
    runs: Paginated<ImportRun>;
    sources: SheetSource[];
    filters: { source: number | null };
}) {
    const [expanded, setExpanded] = useState<number[]>([]);

    const toggle = (id: number) => {
        setExpanded((current) =>
            current.includes(id)
                ? current.filter((value) => value !== id)
                : [...current, id],
        );
    };

    const filterBySource = (value: string) => {
        router.get(runs.index().url, value === 'all' ? {} : { source: value }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const previous = paginator.links[0];
    const next = paginator.links[paginator.links.length - 1];

    return (
        <>
            <Head title="Import history" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Import history"
                        description="Every import run recorded for the configured sources."
                    />

                    <Select
                        value={filters.source ? String(filters.source) : 'all'}
                        onValueChange={filterBySource}
                    >
                        <SelectTrigger className="w-56">
                            <SelectValue placeholder="All sources" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All sources</SelectItem>
                            {sourceList.map((source) => (
                                <SelectItem
                                    key={source.id}
                                    value={String(source.id)}
                                >
                                    {source.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <Card>
                    <CardContent className="overflow-x-auto">
                        {paginator.data.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">
                                No import runs recorded yet.
                            </p>
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-sidebar-border/70 text-left text-muted-foreground">
                                        <th className="py-2 pr-2 font-medium" />
                                        <th className="py-2 pr-4 font-medium">
                                            Source
                                        </th>
                                        <th className="py-2 pr-4 font-medium">
                                            Status
                                        </th>
                                        <th className="py-2 pr-4 font-medium">
                                            Imported
                                        </th>
                                        <th className="py-2 pr-4 font-medium">
                                            Updated
                                        </th>
                                        <th className="py-2 pr-4 font-medium">
                                            Failed
                                        </th>
                                        <th className="py-2 font-medium">
                                            Finished
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {paginator.data.map((run) => (
                                        <Fragment key={run.id}>
                                            <tr className="border-b border-sidebar-border/40">
                                                <td className="py-3 pr-2">
                                                    {(run.issues.length > 0 ||
                                                        run.mappings.length >
                                                            0) && (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label="Toggle details"
                                                            onClick={() =>
                                                                toggle(run.id)
                                                            }
                                                        >
                                                            {expanded.includes(
                                                                run.id,
                                                            ) ? (
                                                                <ChevronDown className="size-4" />
                                                            ) : (
                                                                <ChevronRight className="size-4" />
                                                            )}
                                                        </Button>
                                                    )}
                                                </td>
                                                <td className="py-3 pr-4">
                                                    {run.source ? (
                                                        <Link
                                                            href={sources.edit(
                                                                run.source.id,
                                                            )}
                                                            className="font-medium hover:underline"
                                                        >
                                                            {run.source.name}
                                                        </Link>
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            —
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="py-3 pr-4">
                                                    <RunStatusBadge
                                                        status={run.status}
                                                        label={run.status_label}
                                                    />
                                                </td>
                                                <td className="py-3 pr-4">
                                                    {run.imported_rows}
                                                </td>
                                                <td className="py-3 pr-4">
                                                    {run.updated_rows}
                                                </td>
                                                <td className="py-3 pr-4">
                                                    {run.failed_rows}
                                                </td>
                                                <td className="py-3 text-muted-foreground">
                                                    {formatDate(
                                                        run.finished_at ??
                                                            run.created_at,
                                                    )}
                                                </td>
                                            </tr>
                                            {expanded.includes(run.id) && (
                                                <tr className="border-b border-sidebar-border/40 bg-muted/30">
                                                    <td />
                                                    <td
                                                        colSpan={6}
                                                        className="py-3 pr-4"
                                                    >
                                                        {run.message && (
                                                            <p className="mb-2 text-sm">
                                                                {run.message}
                                                            </p>
                                                        )}

                                                        {run.mappings.length >
                                                            0 && (
                                                            <table className="mb-3 text-xs">
                                                                <thead>
                                                                    <tr className="text-left text-muted-foreground">
                                                                        <th className="pr-4 font-medium">
                                                                            Table
                                                                        </th>
                                                                        <th className="pr-4 font-medium">
                                                                            Inserted
                                                                        </th>
                                                                        <th className="pr-4 font-medium">
                                                                            Updated
                                                                        </th>
                                                                        <th className="pr-4 font-medium">
                                                                            Skipped
                                                                        </th>
                                                                        <th className="font-medium">
                                                                            Failed
                                                                        </th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    {run.mappings.map(
                                                                        (
                                                                            stat,
                                                                        ) => (
                                                                            <tr
                                                                                key={
                                                                                    stat.table
                                                                                }
                                                                            >
                                                                                <td className="pr-4 font-mono">
                                                                                    {
                                                                                        stat.table
                                                                                    }
                                                                                </td>
                                                                                <td className="pr-4">
                                                                                    {
                                                                                        stat.inserted
                                                                                    }
                                                                                </td>
                                                                                <td className="pr-4">
                                                                                    {
                                                                                        stat.updated
                                                                                    }
                                                                                </td>
                                                                                <td className="pr-4">
                                                                                    {
                                                                                        stat.skipped
                                                                                    }
                                                                                </td>
                                                                                <td>
                                                                                    {
                                                                                        stat.failed
                                                                                    }
                                                                                </td>
                                                                            </tr>
                                                                        ),
                                                                    )}
                                                                </tbody>
                                                            </table>
                                                        )}

                                                        <ValidationIssues
                                                            issues={run.issues}
                                                            emptyMessage="No issues reported for this run."
                                                        />
                                                    </td>
                                                </tr>
                                            )}
                                        </Fragment>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </CardContent>
                </Card>

                {paginator.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {paginator.from ?? 0}–{paginator.to ?? 0} of{' '}
                            {paginator.total}
                        </p>
                        <div className="flex items-center gap-2">
                            {previous.url ? (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={previous.url}>Previous</Link>
                                </Button>
                            ) : (
                                <Button variant="outline" size="sm" disabled>
                                    Previous
                                </Button>
                            )}
                            <span className="text-sm text-muted-foreground">
                                Page {paginator.current_page} of{' '}
                                {paginator.last_page}
                            </span>
                            {next.url ? (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={next.url}>Next</Link>
                                </Button>
                            ) : (
                                <Button variant="outline" size="sm" disabled>
                                    Next
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

RunsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Import history',
            href: runs.index(),
        },
    ],
};
