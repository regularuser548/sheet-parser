import { Head } from '@inertiajs/react';
import { SourceForm } from '@/components/sheets/source-form';
import Heading from '@/components/heading';
import { Card, CardContent } from '@/components/ui/card';
import sources from '@/routes/sheets/sources';

export default function CreateSource() {
    return (
        <>
            <Head title="New spreadsheet source" />

            <div className="mx-auto w-full max-w-2xl p-4">
                <Heading
                    title="New spreadsheet source"
                    description="Register a Google Sheet and its service account credentials."
                />

                <Card>
                    <CardContent>
                        <SourceForm
                            actionUrl={sources.store().url}
                            method="post"
                            submitLabel="Create source"
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CreateSource.layout = {
    breadcrumbs: [
        {
            title: 'Spreadsheet sources',
            href: sources.index(),
        },
        {
            title: 'New source',
            href: sources.create(),
        },
    ],
};
