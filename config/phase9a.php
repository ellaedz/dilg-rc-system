<?php

return [
    'connection' => 'phase9a_pgsql',

    'test_schema_pattern' => '/\Aciviclear_phase9a_test_[A-Za-z0-9_]+\z/D',

    'protected_schemas' => [
        'civiclear',
        'public',
        'auth',
        'storage',
        'extensions',
        'graphql_public',
        'realtime',
    ],

    'operational_tables' => [
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'sessions',
        'password_reset_tokens',
    ],

    // Ordered to satisfy CIVICLEAR foreign-key relationships during import.
    'import_tables' => [
        'users',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'records',
        'complaints',
        'violation_reports',
        'report_number_sequences',
        'report_timelines',
    ],

    'final_schema' => 'civiclear',
    'final_import_confirmation' => env('PHASE9A_FINAL_IMPORT_APPROVED'),
    'operational_table_policy' => env('PHASE9A_OPERATIONAL_TABLE_POLICY'),
    'runtime_read_only' => env('PHASE9A_RUNTIME_READ_ONLY', false),
    'runtime_expected_database' => env('PHASE9A_EXPECTED_DATABASE', 'postgres'),
    'write_validation_confirmation' => env('PHASE9A_WRITE_VALIDATION_APPROVED'),
];
