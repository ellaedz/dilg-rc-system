<?php

namespace Tests\Support;

trait CreatesPhase8cResponses
{
    protected function phase8cResponse(
        array $overrides = [],
        int $width = 4,
        int $height = 3
    ): array {
        $scale = min(640 / $width, 640 / $height);
        $resizedWidth = min(640, max(1, (int) floor($width * $scale + 0.5)));
        $resizedHeight = min(640, max(1, (int) floor($height * $scale + 0.5)));

        $base = [
            'image' => [
                'prediction' => 'illegal_parking',
                'confidence' => 0.82,
                'detections' => [[
                    'class_id' => 2,
                    'class_name' => 'illegal_parking',
                    'confidence' => 0.82,
                    'x_center' => $width / 2,
                    'y_center' => $height / 2,
                    'width' => $width,
                    'height' => $height,
                    'x_min' => 0.0,
                    'y_min' => 0.0,
                    'x_max' => (float) $width,
                    'y_max' => (float) $height,
                ]],
                'detection_count' => 1,
                'status' => 'detected',
                'input' => [
                    'original_width' => $width,
                    'original_height' => $height,
                    'resized_width' => $resizedWidth,
                    'resized_height' => $resizedHeight,
                    'pad_x' => intdiv(640 - $resizedWidth, 2),
                    'pad_y' => intdiv(640 - $resizedHeight, 2),
                ],
                'timing_ms' => [
                    'preprocessing' => 10.0,
                    'inference' => 50.0,
                    'postprocessing' => 5.0,
                    'total' => 65.0,
                ],
            ],
            'text' => [
                'prediction' => 'illegal_parking',
                'confidence' => 0.76,
                'model_version' => 'civiclear-nlp-test',
                'decision_source' => 'trained_nlp_model',
                'needs_manual_review' => false,
            ],
            'gis' => [
                'inside_santa_cruz' => true,
                'municipality_name' => 'Santa Cruz',
                'barangay' => null,
                'barangay_assignment_status' => 'barangay_boundary_unavailable',
                'needs_manual_barangay_review' => true,
                'location_context' => 'Inside Santa Cruz; Needs Barangay Review',
            ],
            'fusion' => [
                'final_violation_type' => 'illegal_parking',
                'final_confidence' => 0.79,
                'decision_source' => 'image_text_agreement',
            ],
            'models' => [
                'image' => [
                    'runtime' => 'ai-edge-litert',
                    'runtime_version' => '2.1.6',
                    'model_version' => 'YOLOv8s-TFLite-float16',
                    'sha256' => 'deb4e346701a063cfa39494fd9ab86882269ca827795304db27e60f8e42a7c0f',
                    'class_order' => [
                        'construction_materials',
                        'garbage_debris',
                        'illegal_parking',
                        'road_obstruction',
                        'sidewalk_obstruction',
                    ],
                ],
                'text' => [
                    'model_version' => 'civiclear-nlp-test',
                    'classes' => [
                        'construction_materials',
                        'garbage_debris',
                        'illegal_parking',
                        'no_violation',
                        'road_obstruction',
                        'sidewalk_obstruction',
                    ],
                ],
            ],
            'timing' => [
                'image' => [
                    'preprocessing' => 10.0,
                    'inference' => 50.0,
                    'postprocessing' => 5.0,
                    'total' => 65.0,
                ],
                'total_ms' => 68.0,
            ],
            'review' => [
                'ai_needs_manual_review' => false,
                'ai_manual_review_reasons' => [],
            ],
        ];

        return array_replace_recursive($base, $overrides);
    }
}
