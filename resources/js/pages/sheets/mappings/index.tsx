import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Plus } from 'lucide-react';
import { useState } from 'react';
import { ConfirmButton } from '@/components/sheets/confirm-button';
import { MappingForm } from '@/components/sheets/mapping-form';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import mappings from '@/routes/sheets/mappings';
import sources from '@/routes/sheets/sources';
import type {
    DataTypeOption,
    ImportMapping,
    MappingSchemaStatus,
    SheetTab,
} from '@/types';

function SchemaStatus({ schema }: { schema: MappingSchemaStatus }) {
    if (schema.error) {
        return (
            <Badge
                variant="outline"
                className="border-transparent bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300"
            >
                {schema.error}
            </Badge>
        );
    }

    if (!schema.exists) {
        return (
            <Badge
                variant="outline"
                className="border-transparent bg-yellow-100 text-yellow-800 dark:bg-yellow-950 dark:text-yellow-300"
            >
                Table will be created on import
            </Badge>
        );
    }

    if (schema.missing_columns.length > 0) {
        return (
            <Badge
                variant="outline"
                className="border-transparent bg-yellow-100 text-yellow-800 dark:bg-yellow-950 dark:text-yellow-300"
            >
                Missing columns: {schema.missing_columns.join(', ')}
            </Badge>
        );
    }

    return (
        <Badge
            variant="outline"
            className="border-transparent bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300"
        >
            Schema up to date
        </Badge>
    );
}

export default function MappingsIndex({
    tab,
    mappings: list,
    dataTypes,
}: {
    tab: SheetTab;
    mappings: ImportMapping[];
    dataTypes: DataTypeOption[];
}) {
    const [editing, setEditing] = useState<ImportMapping | 'new' | null>(null);

    const close = () => setEditing(null);

    return (
        <>
            <Head title={`Mappings · ${tab.name}`} />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="space-y-1">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={sources.edit(tab.sheet_source_id)}>
                                <ArrowLeft className="size-4" />
                                {tab.source?.name ?? 'Source'}
                            </Link>
                        </Button>
                        <Heading
                            title={`Mappings for ${tab.name}`}
                            description="Map spreadsheet columns to destination table columns. Imported rows are written to the table below."
                        />
                    </div>
                    <Button onClick={() => setEditing('new')}>
                        <Plus className="size-4" />
                        Add mapping
                    </Button>
                </div>

                {list.length === 0 ? (
                    <Card>
                        <CardContent>
                            <p className="py-6 text-center text-sm text-muted-foreground">
                                No mappings configured for this tab yet.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    list.map((mapping) => (
                        <Card key={mapping.id}>
                            <CardHeader>
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <CardTitle className="font-mono text-base">
                                            {mapping.table_name}
                                        </CardTitle>
                                        <Badge
                                            variant="outline"
                                            className={
                                                mapping.enabled
                                                    ? 'border-transparent bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300'
                                                    : 'border-transparent bg-muted text-muted-foreground'
                                            }
                                        >
                                            {mapping.enabled
                                                ? 'Enabled'
                                                : 'Disabled'}
                                        </Badge>
                                        {mapping.schema && (
                                            <SchemaStatus
                                                schema={mapping.schema}
                                            />
                                        )}
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => setEditing(mapping)}
                                        >
                                            Edit
                                        </Button>
                                        <ConfirmButton
                                            title={`Delete mapping ${mapping.table_name}?`}
                                            description="Deleting a mapping does not remove the destination table."
                                            action={() =>
                                                router.delete(
                                                    mappings.destroy(mapping)
                                                        .url,
                                                )
                                            }
                                        >
                                            Delete
                                        </ConfirmButton>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-sidebar-border/70 text-left text-muted-foreground">
                                            <th className="py-2 pr-4 font-medium">
                                                Source column
                                            </th>
                                            <th className="py-2 pr-4 font-medium">
                                                Destination column
                                            </th>
                                            <th className="py-2 pr-4 font-medium">
                                                Type
                                            </th>
                                            <th className="py-2 pr-4 font-medium">
                                                Nullable
                                            </th>
                                            <th className="py-2 font-medium">
                                                Default
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {mapping.columns.map((column) => (
                                            <tr
                                                key={column.id}
                                                className="border-b border-sidebar-border/40 last:border-b-0"
                                            >
                                                <td className="py-2 pr-4">
                                                    {column.source_column}
                                                </td>
                                                <td className="py-2 pr-4 font-mono">
                                                    {column.destination_column}
                                                </td>
                                                <td className="py-2 pr-4">
                                                    {column.data_type_label ??
                                                        column.data_type}
                                                </td>
                                                <td className="py-2 pr-4">
                                                    {column.nullable
                                                        ? 'Yes'
                                                        : 'No'}
                                                </td>
                                                <td className="py-2 font-mono text-xs">
                                                    {column.default_value ??
                                                        '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </CardContent>
                        </Card>
                    ))
                )}
            </div>

            <Dialog
                open={editing !== null}
                onOpenChange={(open) => !open && close()}
            >
                <DialogContent className="max-h-[90vh] max-w-3xl overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>
                            {editing && editing !== 'new'
                                ? `Edit mapping ${editing.table_name}`
                                : 'New mapping'}
                        </DialogTitle>
                        <DialogDescription>
                            Configure the destination table and how each
                            spreadsheet column is stored.
                        </DialogDescription>
                    </DialogHeader>

                    {editing !== null && (
                        <MappingForm
                            tab={tab}
                            mapping={editing === 'new' ? undefined : editing}
                            dataTypes={dataTypes}
                            onSuccess={close}
                        />
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

MappingsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Spreadsheet sources',
            href: sources.index(),
        },
        {
            title: 'Mappings',
            href: sources.index(),
        },
    ],
};
