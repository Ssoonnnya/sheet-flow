export type User = {
    id: number;
    name: string;
    email: string;
};

export type ColumnMapping = {
    id: number;
    table_mapping_id: number;
    source_column: string;
    destination_column: string;
    data_type: string;
};

export type TableMapping = {
    id: number;
    sheet_tab_id: number;
    destination_table: string;
    unique_columns: string[];
    is_active: boolean;
    columns?: ColumnMapping[];
};

export type SheetTab = {
    id: number;
    sheet_source_id: number;
    name: string;
    header_row: number;
    is_active: boolean;
    mappings?: TableMapping[];
};

export type SheetSource = {
    id: number;
    name: string;
    spreadsheet_id: string;
    is_active: boolean;
    tabs?: SheetTab[];
    created_at?: string | null;
    updated_at?: string | null;
};

export type ImportRunEntry = {
    id: number;
    table_mapping_id: number | null;
    destination_table: string | null;
    rows_read: number;
    rows_written: number;
    rows_skipped: number;
    columns_added: string[] | null;
    status: string;
    error: string | null;
};

export type ImportRun = {
    id: number;
    status: string;
    trigger: string;
    started_at: string | null;
    finished_at: string | null;
    message: string | null;
    entries?: ImportRunEntry[];
    created_at?: string | null;
};

export type PaginationMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type PaginationLinks = {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
};

export type Paginated<T> = {
    data: T[];
    meta: PaginationMeta;
    links: PaginationLinks;
};

export type ValidationErrors = Record<string, string[]>;

export class ApiError extends Error {
    constructor(
        message: string,
        public readonly status: number,
        public readonly errors: ValidationErrors = {},
    ) {
        super(message);
        this.name = 'ApiError';
    }
}

export const COLUMN_TYPES = [
    'string',
    'text',
    'integer',
    'decimal',
    'boolean',
    'date',
    'datetime',
] as const;

export const PAGE_SIZES = [15, 25, 50] as const;

export type PageSize = (typeof PAGE_SIZES)[number];
