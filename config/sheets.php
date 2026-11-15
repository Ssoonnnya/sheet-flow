<?php

use App\Enums\ColumnType;

return [

    /*
    |--------------------------------------------------------------------------
    | Google service account
    |--------------------------------------------------------------------------
    |
    | Absolute path to the service account JSON key. The file itself is never
    | committed. Sharing each spreadsheet with the account email is required
    | before a sync can read it.
    |
    */

    'credentials' => env('GOOGLE_SERVICE_ACCOUNT_JSON', storage_path('app/google/service-account.json')),

    /*
    |--------------------------------------------------------------------------
    | Daily sync time
    |--------------------------------------------------------------------------
    |
    | App timezone, 24-hour "H:i". The scheduler dispatches the sync job then.
    |
    */

    'sync_time' => env('SHEETS_SYNC_TIME', '02:00'),

    /*
    |--------------------------------------------------------------------------
    | HTTP timeout
    |--------------------------------------------------------------------------
    |
    | Seconds the Google Sheets client waits for a single request.
    |
    */

    'http_timeout' => (int) env('SHEETS_HTTP_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Protected tables
    |--------------------------------------------------------------------------
    |
    | Destination names a mapping is not allowed to create or alter.
    |
    */

    'protected_tables' => [
        'users',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'migrations',
        'passkeys',
        'sheet_sources',
        'sheet_tabs',
        'table_mappings',
        'column_mappings',
        'import_runs',
        'import_run_entries',
    ],

    /*
    |--------------------------------------------------------------------------
    | Column types
    |--------------------------------------------------------------------------
    */

    'column_types' => array_map(
        fn (ColumnType $type): string => $type->value,
        ColumnType::cases(),
    ),

    /*
    |--------------------------------------------------------------------------
    | Identifier pattern
    |--------------------------------------------------------------------------
    |
    | Table and column names accepted from configuration.
    |
    */

    'identifier_pattern' => '/^[a-z][a-z0-9_]{0,63}$/',

];
