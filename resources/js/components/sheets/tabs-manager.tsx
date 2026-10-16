import { Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { ConfirmButton } from '@/components/sheets/confirm-button';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import mappings from '@/routes/sheets/mappings';
import tabs from '@/routes/sheets/tabs';
import type { SheetSource, SheetTab } from '@/types';

function TabRow({ tab }: { tab: SheetTab }) {
    const form = useForm({ name: tab.name, enabled: tab.enabled });

    const save = () => {
        form.put(tabs.update(tab).url, { preserveScroll: true });
    };

    const toggleEnabled = (checked: boolean) => {
        router.put(
            tabs.update(tab).url,
            { name: form.data.name, enabled: checked },
            { preserveScroll: true },
        );
    };

    return (
        <tr className="border-b border-sidebar-border/40 last:border-b-0">
            <td className="py-3 pr-4">
                <Input
                    value={form.data.name}
                    onChange={(event) =>
                        form.setData('name', event.target.value)
                    }
                    onBlur={() => {
                        if (form.data.name !== tab.name) {
                            save();
                        }
                    }}
                />
                <InputError className="mt-1" message={form.errors.name} />
            </td>
            <td className="py-3 pr-4">
                <div className="flex items-center gap-2">
                    <Checkbox
                        checked={form.data.enabled}
                        onCheckedChange={(checked) =>
                            toggleEnabled(checked === true)
                        }
                    />
                    <span className="text-sm text-muted-foreground">
                        {tab.enabled ? 'Enabled' : 'Disabled'}
                    </span>
                </div>
            </td>
            <td className="py-3 pr-4">{tab.import_mappings_count ?? 0}</td>
            <td className="py-3">
                <div className="flex flex-wrap items-center gap-2">
                    <Button size="sm" variant="outline" asChild>
                        <Link href={mappings.index(tab)}>
                            Configure mappings
                        </Link>
                    </Button>
                    <ConfirmButton
                        title={`Delete tab ${tab.name}?`}
                        description="Deleting a tab removes its mappings and import markers."
                        action={() => form.delete(tabs.destroy(tab).url)}
                    >
                        Delete
                    </ConfirmButton>
                </div>
            </td>
        </tr>
    );
}

function NewTabForm({ source }: { source: SheetSource }) {
    const form = useForm({ name: '', enabled: true });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(tabs.store(source).url, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <form onSubmit={submit} className="flex flex-wrap items-end gap-3">
            <div className="grid gap-2">
                <Label htmlFor="tab-name">Tab name</Label>
                <Input
                    id="tab-name"
                    value={form.data.name}
                    onChange={(event) =>
                        form.setData('name', event.target.value)
                    }
                    placeholder="Sheet1"
                />
                <InputError message={form.errors.name} />
            </div>
            <Button disabled={form.processing} type="submit">
                Add tab
            </Button>
        </form>
    );
}

export function TabsManager({ source }: { source: SheetSource }) {
    const tabs = source.tabs ?? [];

    return (
        <div className="space-y-6">
            {tabs.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No tabs configured yet. Add the worksheet names to import.
                </p>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-sidebar-border/70 text-left text-muted-foreground">
                                <th className="py-2 pr-4 font-medium">Name</th>
                                <th className="py-2 pr-4 font-medium">
                                    Status
                                </th>
                                <th className="py-2 pr-4 font-medium">
                                    Mappings
                                </th>
                                <th className="py-2 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {tabs.map((tab) => (
                                <TabRow key={tab.id} tab={tab} />
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            <NewTabForm source={source} />
        </div>
    );
}
