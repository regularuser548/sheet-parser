import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, RefreshCw } from 'lucide-react';
import { RunStatusBadge } from '@/components/sheets/run-status-badge';
import { ValidationIssues } from '@/components/sheets/validation-issues';
import { ConfirmButton } from '@/components/sheets/confirm-button';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import sheets from '@/routes/sheets';
import sources from '@/routes/sheets/sources';
import type { SheetSource } from '@/types';

export default function SourcesIndex({
    sources: list,
}: {
    sources: SheetSource[];
}) {
    const { flash } = usePage();

    const validate = () => {
        router.post(sheets.validate().url);
    };

    return (
        <>
            <Head title="Spreadsheet sources" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Spreadsheet sources"
                        description="Configure which Google Sheets are imported into the database."
                    />
                    <div className="flex items-center gap-2">
                        <Button variant="outline" onClick={validate}>
                            <RefreshCw className="size-4" />
                            Validate configuration
                        </Button>
                        <Button asChild>
                            <Link href={sources.create()}>
                                <Plus className="size-4" />
                                New source
                            </Link>
                        </Button>
                    </div>
                </div>

                {flash?.validation && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Validation result
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ValidationIssues
                                issues={flash.validation.issues}
                                emptyMessage="Configuration is valid."
                            />
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardContent className="overflow-x-auto">
                        {list.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">
                                No spreadsheet sources configured yet.
                            </p>
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-sidebar-border/70 text-left text-muted-foreground">
                                        <th className="py-2 pr-4 font-medium">
                                            Name
                                        </th>
                                        <th className="py-2 pr-4 font-medium">
                                            Spreadsheet ID
                                        </th>
                                        <th className="py-2 pr-4 font-medium">
                                            Tabs
                                        </th>
                                        <th className="py-2 pr-4 font-medium">
                                            Status
                                        </th>
                                        <th className="py-2 pr-4 font-medium">
                                            Last run
                                        </th>
                                        <th className="py-2 font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {list.map((source) => (
                                        <tr
                                            key={source.id}
                                            className="border-b border-sidebar-border/40 last:border-b-0"
                                        >
                                            <td className="py-3 pr-4 font-medium">
                                                {source.name}
                                            </td>
                                            <td className="py-3 pr-4 font-mono text-xs text-muted-foreground">
                                                {source.spreadsheet_id}
                                            </td>
                                            <td className="py-3 pr-4">
                                                {source.enabled_tabs_count ?? 0}
                                                {' / '}
                                                {source.tabs_count ?? 0}
                                            </td>
                                            <td className="py-3 pr-4">
                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        source.enabled
                                                            ? 'border-transparent bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300'
                                                            : 'border-transparent bg-muted text-muted-foreground'
                                                    }
                                                >
                                                    {source.enabled
                                                        ? 'Enabled'
                                                        : 'Disabled'}
                                                </Badge>
                                            </td>
                                            <td className="py-3 pr-4">
                                                {source.latest_run ? (
                                                    <div className="flex flex-col gap-1">
                                                        <RunStatusBadge
                                                            status={
                                                                source
                                                                    .latest_run
                                                                    .status
                                                            }
                                                            label={
                                                                source
                                                                    .latest_run
                                                                    .status_label
                                                            }
                                                        />
                                                        <span className="text-xs text-muted-foreground">
                                                            {
                                                                source
                                                                    .latest_run
                                                                    .imported_rows
                                                            }{' '}
                                                            imported ·{' '}
                                                            {
                                                                source
                                                                    .latest_run
                                                                    .updated_rows
                                                            }{' '}
                                                            updated ·{' '}
                                                            {
                                                                source
                                                                    .latest_run
                                                                    .failed_rows
                                                            }{' '}
                                                            failed
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        Never
                                                    </span>
                                                )}
                                            </td>
                                            <td className="py-3">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={sources.edit(
                                                                source,
                                                            )}
                                                        >
                                                            Edit
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="secondary"
                                                        onClick={() =>
                                                            router.post(
                                                                sources.import(
                                                                    source,
                                                                ).url,
                                                            )
                                                        }
                                                    >
                                                        Import now
                                                    </Button>
                                                    <ConfirmButton
                                                        title={`Delete ${source.name}?`}
                                                        description="Deleting a source removes its tabs and mappings. Imported rows are kept."
                                                        action={() =>
                                                            router.delete(
                                                                sources.destroy(
                                                                    source,
                                                                ).url,
                                                            )
                                                        }
                                                    >
                                                        Delete
                                                    </ConfirmButton>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

SourcesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Spreadsheet sources',
            href: sources.index(),
        },
    ],
};
