import { useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import mappings from '@/routes/sheets/mappings';
import type { DataTypeOption, ImportMapping, SheetTab } from '@/types';

type MappingColumnInput = {
    source_column: string;
    destination_column: string;
    data_type: string;
    nullable: boolean;
    default_value: string;
};

type MappingFormData = {
    table_name: string;
    enabled: boolean;
    columns: MappingColumnInput[];
};

const emptyColumn = (): MappingColumnInput => ({
    source_column: '',
    destination_column: '',
    data_type: 'string',
    nullable: true,
    default_value: '',
});

export function MappingForm({
    tab,
    mapping,
    dataTypes,
    onSuccess,
}: {
    tab: SheetTab;
    mapping?: ImportMapping;
    dataTypes: DataTypeOption[];
    onSuccess: () => void;
}) {
    const form = useForm<MappingFormData>({
        table_name: mapping?.table_name ?? '',
        enabled: mapping?.enabled ?? true,
        columns: mapping?.columns.map((column) => ({
            source_column: column.source_column,
            destination_column: column.destination_column,
            data_type: column.data_type,
            nullable: column.nullable,
            default_value: column.default_value ?? '',
        })) ?? [emptyColumn()],
    });

    const errors = form.errors as Record<string, string | undefined>;

    const setColumn = (
        index: number,
        key: keyof MappingColumnInput,
        value: string | boolean,
    ) => {
        const columns = form.data.columns.map((column, position) =>
            position === index ? { ...column, [key]: value } : column,
        );
        form.setData('columns', columns);
    };

    const addColumn = () => {
        form.setData('columns', [...form.data.columns, emptyColumn()]);
    };

    const removeColumn = (index: number) => {
        form.setData(
            'columns',
            form.data.columns.filter((_, position) => position !== index),
        );
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onSuccess();
            },
        };

        if (mapping) {
            form.put(mappings.update(mapping).url, options);
        } else {
            form.post(mappings.store(tab).url, options);
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="table_name">Destination table</Label>
                    <Input
                        id="table_name"
                        value={form.data.table_name}
                        onChange={(event) =>
                            form.setData('table_name', event.target.value)
                        }
                        placeholder="imported_products"
                        className="font-mono"
                    />
                    <InputError message={errors.table_name} />
                </div>

                <div className="flex items-end gap-3 pb-2">
                    <Checkbox
                        id="mapping-enabled"
                        checked={form.data.enabled}
                        onCheckedChange={(checked) =>
                            form.setData('enabled', checked === true)
                        }
                    />
                    <Label htmlFor="mapping-enabled">Enabled</Label>
                </div>
            </div>

            <div className="space-y-3">
                <div className="flex items-center justify-between">
                    <Label>Columns</Label>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={addColumn}
                    >
                        <Plus className="size-4" />
                        Add column
                    </Button>
                </div>

                <InputError message={errors.columns} />

                <div className="space-y-3">
                    {form.data.columns.map((column, index) => (
                        <div
                            key={index}
                            className="grid gap-3 rounded-lg border border-sidebar-border/70 p-3 sm:grid-cols-[1fr_1fr_auto]"
                        >
                            <div className="grid gap-1">
                                <Label
                                    htmlFor={`source-${index}`}
                                    className="text-xs text-muted-foreground"
                                >
                                    Source column
                                </Label>
                                <Input
                                    id={`source-${index}`}
                                    value={column.source_column}
                                    onChange={(event) =>
                                        setColumn(
                                            index,
                                            'source_column',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Product name"
                                />
                                <InputError
                                    message={
                                        errors[`columns.${index}.source_column`]
                                    }
                                />
                            </div>

                            <div className="grid gap-1">
                                <Label
                                    htmlFor={`destination-${index}`}
                                    className="text-xs text-muted-foreground"
                                >
                                    Destination column
                                </Label>
                                <Input
                                    id={`destination-${index}`}
                                    value={column.destination_column}
                                    onChange={(event) =>
                                        setColumn(
                                            index,
                                            'destination_column',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="name"
                                    className="font-mono"
                                />
                                <InputError
                                    message={
                                        errors[
                                            `columns.${index}.destination_column`
                                        ]
                                    }
                                />
                            </div>

                            <div className="grid gap-1">
                                <Label className="text-xs text-muted-foreground">
                                    Type
                                </Label>
                                <div className="flex items-center gap-2">
                                    <Select
                                        value={column.data_type}
                                        onValueChange={(value) =>
                                            setColumn(index, 'data_type', value)
                                        }
                                    >
                                        <SelectTrigger className="w-36">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {dataTypes.map((type) => (
                                                <SelectItem
                                                    key={type.value}
                                                    value={type.value}
                                                >
                                                    {type.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>

                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => removeColumn(index)}
                                        disabled={
                                            form.data.columns.length === 1
                                        }
                                        aria-label="Remove column"
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                                <InputError
                                    message={
                                        errors[`columns.${index}.data_type`]
                                    }
                                />
                            </div>

                            <div className="grid gap-1 sm:col-span-2">
                                <Label
                                    htmlFor={`default-${index}`}
                                    className="text-xs text-muted-foreground"
                                >
                                    Default value (used when the cell is empty
                                    and the column is not nullable)
                                </Label>
                                <Input
                                    id={`default-${index}`}
                                    value={column.default_value}
                                    onChange={(event) =>
                                        setColumn(
                                            index,
                                            'default_value',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="(none)"
                                />
                                <InputError
                                    message={
                                        errors[`columns.${index}.default_value`]
                                    }
                                />
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id={`nullable-${index}`}
                                    checked={column.nullable}
                                    onCheckedChange={(checked) =>
                                        setColumn(
                                            index,
                                            'nullable',
                                            checked === true,
                                        )
                                    }
                                />
                                <Label
                                    htmlFor={`nullable-${index}`}
                                    className="text-sm text-muted-foreground"
                                >
                                    Nullable
                                </Label>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            <Button disabled={form.processing}>
                {mapping ? 'Save mapping' : 'Create mapping'}
            </Button>
        </form>
    );
}
