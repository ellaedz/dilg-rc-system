<?php

return [
    'dispatcher' => env('REPORT_AI_DISPATCHER', 'inline'),
    'project' => env('GOOGLE_CLOUD_PROJECT'),
    'location' => env('CLOUD_TASKS_LOCATION'),
    'queue' => env('CLOUD_TASKS_QUEUE'),
    'handler_url' => env('CLOUD_TASKS_HANDLER_URL'),
    'oidc_service_account_email' => env('CLOUD_TASKS_OIDC_SERVICE_ACCOUNT_EMAIL'),
    'oidc_service_account_subject' => env('CLOUD_TASKS_OIDC_SERVICE_ACCOUNT_SUBJECT'),
    'oidc_audience' => env('CLOUD_TASKS_OIDC_AUDIENCE'),
    'allowed_issuers' => [
        'accounts.google.com',
        'https://accounts.google.com',
    ],
    'clock_skew_seconds' => max(0, (int) env('CLOUD_TASKS_OIDC_CLOCK_SKEW_SECONDS', 60)),
    'maximum_token_age_seconds' => max(
        60,
        (int) env('CLOUD_TASKS_OIDC_MAX_TOKEN_AGE_SECONDS', 3600)
    ),
    'create_timeout_seconds' => max(
        1,
        (int) env('CLOUD_TASKS_CREATE_TIMEOUT_SECONDS', 10)
    ),
    'dispatch_deadline_seconds' => max(
        15,
        (int) env('CLOUD_TASKS_DISPATCH_DEADLINE_SECONDS', 45)
    ),
    'creation_claim_seconds' => max(
        15,
        (int) env('CLOUD_TASKS_CREATION_CLAIM_SECONDS', 30)
    ),
    'stale_dispatch_seconds' => max(
        60,
        (int) env('CLOUD_TASKS_STALE_DISPATCH_SECONDS', 900)
    ),
    'recovery_batch_size' => max(
        1,
        min(200, (int) env('CLOUD_TASKS_RECOVERY_BATCH_SIZE', 50))
    ),
    'payload_version' => 'v1',
];
