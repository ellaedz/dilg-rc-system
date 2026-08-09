<?php

return [
    'schema' => 'civiclear',
    'expected_database' => env('PHASE9B_EXPECTED_DATABASE', 'postgres'),
    'stage3_confirmation' => env('PHASE9B_STAGE3_APPROVED'),
    'stage4_confirmation' => env('PHASE9B_STAGE4_APPROVED'),
    'pg_dump_path' => env('PHASE9B_PG_DUMP_PATH'),
    'pg_restore_path' => env('PHASE9B_PG_RESTORE_PATH'),
    'backup_timeout_seconds' => (int) env('PHASE9B_BACKUP_TIMEOUT_SECONDS', 300),
];
