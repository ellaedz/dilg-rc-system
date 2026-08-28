<?php

return [
    'driver' => env('REPORT_PHOTO_STORAGE_DRIVER', 'local'),
    'disk' => env('REPORT_PHOTO_DISK', 'report_photos'),
    'supabase_disk' => 'supabase_report_photos',
    'signed_url_ttl_seconds' => (int) env('REPORT_PHOTO_SIGNED_URL_TTL_SECONDS', 120),
    'quarantine_disk' => env('REPORT_PHOTO_QUARANTINE_DISK', 'report_photo_quarantine'),
    'max_bytes' => (int) env('REPORT_PHOTO_MAX_BYTES', 5 * 1024 * 1024),
    'max_sanitized_bytes' => (int) env(
        'REPORT_PHOTO_MAX_SANITIZED_BYTES',
        5 * 1024 * 1024
    ),
    'max_width' => (int) env('REPORT_PHOTO_MAX_WIDTH', 8000),
    'max_height' => (int) env('REPORT_PHOTO_MAX_HEIGHT', 8000),
    'max_pixels' => (int) env('REPORT_PHOTO_MAX_PIXELS', 20_000_000),
    'jpeg_quality' => (int) env('REPORT_PHOTO_JPEG_QUALITY', 85),
    'png_compression' => (int) env('REPORT_PHOTO_PNG_COMPRESSION', 6),
    'jpeg_background' => [255, 255, 255],
    'processing_lease_seconds' => (int) env('REPORT_PHOTO_PROCESSING_LEASE_SECONDS', 300),
    'quarantine_enabled' => filter_var(
        env('REPORT_PHOTO_QUARANTINE_ENABLED', false),
        FILTER_VALIDATE_BOOL
    ),
    'quarantine_ttl_hours' => (int) env('REPORT_PHOTO_QUARANTINE_TTL_HOURS', 24),
];
