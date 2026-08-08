<?php

namespace App\Services;

use App\Exceptions\AiProcessingException;

class FastApiInferenceResponseValidator
{
    public function validate(array $payload): array
    {
        foreach (['image', 'text', 'gis', 'fusion', 'models', 'timing', 'review'] as $section) {
            $this->requireArray($payload, $section);
        }

        $image = $this->validateImage($payload['image']);
        $text = $this->validateText($payload['text']);
        $gis = $this->validateGis($payload['gis']);
        $fusion = $this->validateFusion($payload['fusion']);
        $models = $this->validateModels($payload['models']);
        $timing = $this->validateTiming($payload['timing']);
        $review = $this->validateReview($payload['review']);

        return compact('image', 'text', 'gis', 'fusion', 'models', 'timing', 'review');
    }

    private function validateImage(array $image): array
    {
        $prediction = $this->nullableAllowedString(
            $image,
            'prediction',
            config('ai_inference.image_classes', [])
        );
        $confidence = $this->probability($image, 'confidence');
        $status = $this->allowedString(
            $image,
            'status',
            config('ai_inference.image_statuses', [])
        );
        $input = $this->requireArray($image, 'input');
        $timing = $this->requireArray($image, 'timing_ms');
        $detections = $this->requireArray($image, 'detections');
        $detectionCount = $this->boundedInteger(
            $image,
            'detection_count',
            0,
            (int) config('ai_inference.max_detections', 20)
        );

        foreach (['original_width', 'original_height', 'resized_width', 'resized_height'] as $key) {
            $input[$key] = $this->boundedInteger($input, $key, 1, 100_000);
        }
        foreach (['pad_x', 'pad_y'] as $key) {
            $input[$key] = $this->boundedInteger($input, $key, 0, 100_000);
        }
        $this->validateTimingMap($timing, [
            'preprocessing',
            'inference',
            'postprocessing',
            'total',
        ]);

        $maxDetections = (int) config('ai_inference.max_detections', 20);
        if (count($detections) > $maxDetections || $detectionCount !== count($detections)) {
            $this->invalid();
        }
        if (($status === 'no_detection') !== ($detectionCount === 0)) {
            $this->invalid();
        }
        if (($prediction === null) !== ($detectionCount === 0)) {
            $this->invalid();
        }

        $classes = config('ai_inference.image_classes', []);
        $validatedDetections = [];
        foreach ($detections as $detection) {
            if (! is_array($detection)) {
                $this->invalid();
            }
            $classId = $this->boundedInteger($detection, 'class_id', 0, count($classes) - 1);
            $className = $this->allowedString($detection, 'class_name', $classes);
            if (($classes[$classId] ?? null) !== $className) {
                $this->invalid();
            }
            $boxConfidence = $this->probability($detection, 'confidence');
            $coordinates = [];
            foreach (['x_center', 'y_center', 'width', 'height', 'x_min', 'y_min', 'x_max', 'y_max'] as $key) {
                $coordinates[$key] = $this->finiteNumber($detection, $key);
            }

            if ($coordinates['width'] < 0
                || $coordinates['height'] < 0
                || $coordinates['x_min'] < 0
                || $coordinates['y_min'] < 0
                || $coordinates['x_max'] > $input['original_width']
                || $coordinates['y_max'] > $input['original_height']
                || $coordinates['x_min'] >= $coordinates['x_max']
                || $coordinates['y_min'] >= $coordinates['y_max']) {
                $this->invalid();
            }

            $validatedDetections[] = [
                'class_id' => $classId,
                'class_name' => $className,
                'confidence' => $boxConfidence,
                ...$coordinates,
            ];
        }

        return [
            'prediction' => $prediction,
            'confidence' => $confidence,
            'detections' => $validatedDetections,
            'detection_count' => $detectionCount,
            'status' => $status,
            'input' => $input,
            'timing_ms' => $timing,
        ];
    }

    private function validateText(array $text): array
    {
        return [
            'prediction' => $this->nullableAllowedString(
                $text,
                'prediction',
                config('ai_inference.text_classes', [])
            ),
            'confidence' => $this->probability($text, 'confidence'),
            'model_version' => $this->boundedString($text, 'model_version'),
            'decision_source' => $this->allowedString(
                $text,
                'decision_source',
                config('ai_inference.text_decision_sources', [])
            ),
            'needs_manual_review' => $this->boolean($text, 'needs_manual_review'),
        ];
    }

    private function validateGis(array $gis): array
    {
        return [
            'inside_santa_cruz' => $this->boolean($gis, 'inside_santa_cruz'),
            'municipality_name' => $this->nullableBoundedString($gis, 'municipality_name'),
            'barangay' => $this->nullableBoundedString($gis, 'barangay'),
            'barangay_assignment_status' => $this->allowedString(
                $gis,
                'barangay_assignment_status',
                config('ai_inference.gis_statuses', [])
            ),
            'needs_manual_barangay_review' => $this->boolean(
                $gis,
                'needs_manual_barangay_review'
            ),
            'location_context' => $this->boundedString($gis, 'location_context'),
        ];
    }

    private function validateFusion(array $fusion): array
    {
        return [
            'final_violation_type' => $this->nullableAllowedString(
                $fusion,
                'final_violation_type',
                config('ai_inference.image_classes', [])
            ),
            'final_confidence' => $this->probability($fusion, 'final_confidence'),
            'decision_source' => $this->allowedString(
                $fusion,
                'decision_source',
                config('ai_inference.decision_sources', [])
            ),
        ];
    }

    private function validateModels(array $models): array
    {
        $image = $this->requireArray($models, 'image');
        $text = $this->requireArray($models, 'text');
        $classOrder = $this->requireArray($image, 'class_order');
        $expectedClasses = config('ai_inference.image_classes', []);

        if ($classOrder !== $expectedClasses) {
            $this->invalid();
        }

        $textClasses = $this->requireArray($text, 'classes');
        if (count($textClasses) > count(config('ai_inference.text_classes', []))) {
            $this->invalid();
        }
        foreach ($textClasses as $class) {
            if (! is_string($class)
                || ! in_array($class, config('ai_inference.text_classes', []), true)) {
                $this->invalid();
            }
        }

        $hash = $this->boundedString($image, 'sha256');
        if (! preg_match('/\A[a-f0-9]{64}\z/D', $hash)) {
            $this->invalid();
        }

        return [
            'image' => [
                'runtime' => $this->boundedString($image, 'runtime'),
                'runtime_version' => $this->boundedString($image, 'runtime_version'),
                'model_version' => $this->boundedString($image, 'model_version'),
                'sha256' => $hash,
                'class_order' => $classOrder,
            ],
            'text' => [
                'model_version' => $this->boundedString($text, 'model_version'),
                'classes' => array_values($textClasses),
            ],
        ];
    }

    private function validateTiming(array $timing): array
    {
        $image = $this->requireArray($timing, 'image');
        $this->validateTimingMap($image, [
            'preprocessing',
            'inference',
            'postprocessing',
            'total',
        ]);

        return [
            'image' => $image,
            'total_ms' => $this->nonNegativeNumber($timing, 'total_ms'),
        ];
    }

    private function validateReview(array $review): array
    {
        $needsReview = $this->boolean($review, 'ai_needs_manual_review');
        $reasons = $this->requireArray($review, 'ai_manual_review_reasons');
        $allowed = config('ai_inference.review_reasons', []);

        if (count($reasons) > count($allowed) || count($reasons) !== count(array_unique($reasons))) {
            $this->invalid();
        }
        foreach ($reasons as $reason) {
            if (! is_string($reason) || ! in_array($reason, $allowed, true)) {
                $this->invalid();
            }
        }
        if ($needsReview !== ($reasons !== [])) {
            $this->invalid();
        }

        return [
            'ai_needs_manual_review' => $needsReview,
            'ai_manual_review_reasons' => array_values($reasons),
        ];
    }

    private function validateTimingMap(array $timing, array $required): void
    {
        if (count($timing) > 12) {
            $this->invalid();
        }
        foreach ($required as $key) {
            $timing[$key] = $this->nonNegativeNumber($timing, $key);
        }
    }

    private function requireArray(array $source, string $key): array
    {
        if (! array_key_exists($key, $source) || ! is_array($source[$key])) {
            $this->invalid();
        }

        return $source[$key];
    }

    private function boolean(array $source, string $key): bool
    {
        if (! array_key_exists($key, $source) || ! is_bool($source[$key])) {
            $this->invalid();
        }

        return $source[$key];
    }

    private function boundedString(array $source, string $key): string
    {
        if (! array_key_exists($key, $source) || ! is_string($source[$key])) {
            $this->invalid();
        }
        $value = $source[$key];
        if ($value === '' || strlen($value) > (int) config('ai_inference.max_string_length', 255)) {
            $this->invalid();
        }

        return $value;
    }

    private function nullableBoundedString(array $source, string $key): ?string
    {
        if (! array_key_exists($key, $source)) {
            $this->invalid();
        }
        if ($source[$key] === null) {
            return null;
        }

        return $this->boundedString($source, $key);
    }

    private function allowedString(array $source, string $key, array $allowed): string
    {
        $value = $this->boundedString($source, $key);
        if (! in_array($value, $allowed, true)) {
            $this->invalid();
        }

        return $value;
    }

    private function nullableAllowedString(array $source, string $key, array $allowed): ?string
    {
        $value = $this->nullableBoundedString($source, $key);
        if ($value !== null && ! in_array($value, $allowed, true)) {
            $this->invalid();
        }

        return $value;
    }

    private function probability(array $source, string $key): float
    {
        $value = $this->finiteNumber($source, $key);
        if ($value < 0 || $value > 1) {
            $this->invalid();
        }

        return $value;
    }

    private function nonNegativeNumber(array $source, string $key): float
    {
        $value = $this->finiteNumber($source, $key);
        if ($value < 0) {
            $this->invalid();
        }

        return $value;
    }

    private function finiteNumber(array $source, string $key): float
    {
        if (! array_key_exists($key, $source) || ! is_int($source[$key]) && ! is_float($source[$key])) {
            $this->invalid();
        }
        $value = (float) $source[$key];
        if (! is_finite($value)) {
            $this->invalid();
        }

        return $value;
    }

    private function boundedInteger(array $source, string $key, int $minimum, int $maximum): int
    {
        if (! array_key_exists($key, $source) || ! is_int($source[$key])) {
            $this->invalid();
        }
        $value = $source[$key];
        if ($value < $minimum || $value > $maximum) {
            $this->invalid();
        }

        return $value;
    }

    private function invalid(): never
    {
        throw new AiProcessingException(
            'FASTAPI_SCHEMA_INVALID',
            'AI processing returned an invalid response.'
        );
    }
}
