export type SheetSource = {
    id: number;
    name: string;
    spreadsheet_id: string;
    credentials_path: string | null;
    enabled: boolean;
    tabs_count?: number;
    enabled_tabs_count?: number;
    tabs?: SheetTab[];
    latest_run?: ImportRun | null;
};

export type SheetTab = {
    id: number;
    sheet_source_id: number;
    name: string;
    enabled: boolean;
    import_mappings_count?: number;
    import_mappings?: ImportMapping[];
    source?: SheetSourceSummary;
};

export type SheetSourceSummary = {
    id: number;
    name: string;
    spreadsheet_id: string;
};

export type ImportMapping = {
    id: number;
    table_name: string;
    enabled: boolean;
    columns: ColumnMapping[];
    schema?: MappingSchemaStatus;
};

export type MappingSchemaStatus = {
    table: string;
    exists: boolean;
    missing_columns: string[];
    satisfied: boolean;
    error: string | null;
};

export type ColumnMapping = {
    id?: number;
    source_column: string;
    destination_column: string;
    data_type: string;
    data_type_label?: string;
    nullable: boolean;
    default_value: string | null;
};

export type ImportRun = {
    id: number;
    status: ImportRunStatus;
    status_label: string;
    status_classes: string;
    imported_rows: number;
    updated_rows: number;
    failed_rows: number;
    message: string | null;
    issues: ValidationIssue[];
    mappings: MappingStatistic[];
    started_at: string | null;
    finished_at: string | null;
    created_at: string | null;
    source?: SheetSourceSummary;
};

export type ImportRunStatus =
    | 'pending'
    | 'running'
    | 'success'
    | 'partial'
    | 'failed';

export type MappingStatistic = {
    table: string;
    inserted: number;
    updated: number;
    skipped: number;
    failed: number;
};

export type ValidationIssue = {
    level: 'error' | 'warning';
    subject: string;
    message: string;
};

export type ValidationReport = {
    valid: boolean;
    errors: number;
    warnings: number;
    issues: ValidationIssue[];
};

export type DataTypeOption = {
    value: string;
    label: string;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};
