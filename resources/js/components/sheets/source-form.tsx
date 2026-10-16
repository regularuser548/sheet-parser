import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { SheetSource } from '@/types';

type SourceFormData = {
    name: string;
    spreadsheet_id: string;
    credentials_path: string;
    enabled: boolean;
};

export function SourceForm({
    source,
    actionUrl,
    method,
    submitLabel,
}: {
    source?: SheetSource;
    actionUrl: string;
    method: 'post' | 'put';
    submitLabel: string;
}) {
    const form = useForm<SourceFormData>({
        name: source?.name ?? '',
        spreadsheet_id: source?.spreadsheet_id ?? '',
        credentials_path: source?.credentials_path ?? '',
        enabled: source?.enabled ?? true,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form[method](actionUrl);
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="grid gap-2">
                <Label htmlFor="name">Name</Label>
                <Input
                    id="name"
                    value={form.data.name}
                    onChange={(event) =>
                        form.setData('name', event.target.value)
                    }
                    placeholder="Marketing sheet"
                />
                <InputError message={form.errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="spreadsheet_id">Spreadsheet ID</Label>
                <Input
                    id="spreadsheet_id"
                    value={form.data.spreadsheet_id}
                    onChange={(event) =>
                        form.setData('spreadsheet_id', event.target.value)
                    }
                    placeholder="1AbC..."
                    className="font-mono"
                />
                <p className="text-xs text-muted-foreground">
                    The ID found in the Google Sheets URL between
                    &quot;/d/&quot; and &quot;/edit&quot;.
                </p>
                <InputError message={form.errors.spreadsheet_id} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="credentials_path">
                    Service account credentials
                </Label>
                <Input
                    id="credentials_path"
                    value={form.data.credentials_path}
                    onChange={(event) =>
                        form.setData('credentials_path', event.target.value)
                    }
                    placeholder="storage/app/google/service-account.json"
                    className="font-mono"
                />
                <p className="text-xs text-muted-foreground">
                    Optional per-source credentials file. Falls back to the
                    shared GOOGLE_SHEETS_CREDENTIALS setting when empty.
                </p>
                <InputError message={form.errors.credentials_path} />
            </div>

            <div className="flex items-center gap-3">
                <Checkbox
                    id="enabled"
                    checked={form.data.enabled}
                    onCheckedChange={(checked) =>
                        form.setData('enabled', checked === true)
                    }
                />
                <Label htmlFor="enabled">Enabled for scheduled imports</Label>
            </div>

            <Button disabled={form.processing}>{submitLabel}</Button>
        </form>
    );
}
