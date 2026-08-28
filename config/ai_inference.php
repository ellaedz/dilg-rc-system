<?php

return [
    'url' => env('AI_INFERENCE_URL', 'http://127.0.0.1:9000'),
    'auth_mode' => env('AI_INFERENCE_AUTH_MODE', 'none'),
    'entra_resource' => env('AI_INFERENCE_ENTRA_RESOURCE'),
    'connect_timeout_seconds' => max(1, (int) env('AI_INFERENCE_CONNECT_TIMEOUT', 3)),
    'timeout_seconds' => max(1, (int) env('AI_INFERENCE_TIMEOUT', 30)),
    'service_timeout_seconds' => max(1, (int) env('AI_INFERENCE_SERVICE_TIMEOUT_SECONDS', 40)),
    'processing_lease_seconds' => max(30, (int) env('AI_INFERENCE_LEASE_SECONDS', 90)),
    'max_response_bytes' => max(4096, (int) env('AI_INFERENCE_MAX_RESPONSE_BYTES', 262144)),

    'image_classes' => [
        'construction_materials',
        'garbage_debris',
        'illegal_parking',
        'road_obstruction',
        'sidewalk_obstruction',
    ],
    'text_classes' => [
        'construction_materials',
        'garbage_debris',
        'illegal_parking',
        'road_obstruction',
        'sidewalk_obstruction',
        'no_violation',
    ],
    'image_statuses' => ['detected', 'no_detection'],
    'gis_statuses' => [
        'outside_coverage',
        'barangay_boundary_unavailable',
        'auto_detected',
        'barangay_not_matched',
    ],
    'decision_sources' => [
        'image_text_agreement',
        'strong_disagreement_manual_review',
        'image_priority',
        'nlp_priority',
        'weak_evidence_manual_review',
    ],
    'text_decision_sources' => [
        'trained_nlp_model',
        'temporary_rule_fallback',
    ],
    'review_reasons' => [
        'no_image_detection',
        'low_image_confidence',
        'low_text_confidence',
        'image_text_disagreement',
        'unsupported_category',
        'insufficient_text',
        'insufficient_fusion_confidence',
    ],
    'max_detections' => 20,
    'max_string_length' => 255,
];
