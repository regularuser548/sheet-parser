import { Head, router, usePage } from '@inertiajs/react';
import { Play, ShieldCheck } from 'lucide-react';
import { ConfirmButton } from '@/components/sheets/confirm-button';
import { SourceForm } from '@/components/sheets/source-form';
import { TabsManager } from '@/components/sheets/tabs-manager';
import { ValidationIssues } from '@/components/sheets/validation-issues';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import sheets from '@/routes/sheets';
import sources from '@/routes/sheets/sources';
import type { SheetSource } from '@/types';

export default function EditSource({ source }: { source: SheetSource }) {
    const { flash } = usePage();

    return (
        <>
            <Head title={`Edit ${source.name}`} />

            <div className="mx-auto flex w-full max-w-4xl flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title={source.name}
                        description={`Spreadsheet ${source.spreadsheet_id}`}
                    />
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            variant="outline"
                            onClick={() =>
                                router.post(sheets.validate().url, {
                                    source: source.id,
                                })
                            }
                        >
                            <ShieldCheck className="size-4" />
                            Validate
                        </Button>
                        <Button
                            variant="secondary"
                            onClick={() =>
                                router.post(sources.import(source).url)
                            }
                        >
                            <Play className="size-4" />
                            Import now
                        </Button>
                        <ConfirmButton
                            title={`Delete ${source.name}?`}
                            description="Deleting a source removes its tabs and mappings. Imported rows are kept."
                            action={() =>
                                router.delete(sources.destroy(source).url)
                            }
                        >
                            Delete source
                        </ConfirmButton>
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
                    <CardHeader>
                        <CardTitle className="text-base">
                            Source settings
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <SourceForm
                            source={source}
                            actionUrl={sources.update(source).url}
                            method="put"
                            submitLabel="Save changes"
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Tabs</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <TabsManager source={source} />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

EditSource.layout = {
    breadcrumbs: [
        {
            title: 'Spreadsheet sources',
            href: sources.index(),
        },
        {
            title: 'Edit source',
            href: sources.index(),
        },
    ],
};
