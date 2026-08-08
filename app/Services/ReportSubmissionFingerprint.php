<?php

namespace App\Services;

use App\Models\ViolationReport;
use App\Support\CitizenViolationType;
use Carbon\CarbonImmutable;

class ReportSubmissionFingerprint
{
    public function fromValidated(array $validated): string
    {
        return $this->hash([
            'description' => (string) $validated['description'],
            'selected_violation_type' => CitizenViolationType::forStorage(
                $validated['selected_violation_type'] ?? null
            ),
            'latitude' => $this->decimal($validated['latitude'], 8),
            'longitude' => $this->decimal($validated['longitude'], 8),
            'gps_accuracy' => array_key_exists('gps_accuracy', $validated)
                && $validated['gps_accuracy'] !== null
                    ? $this->decimal($validated['gps_accuracy'], 2)
                    : null,
            'timestamp' => $this->timestamp($validated['timestamp']),
            'contact_number' => $validated['contact_number'] ?? null,
        ]);
    }

    public function fromReport(ViolationReport $report): string
    {
        return $this->hash([
            'description' => (string) $report->description,
            'selected_violation_type' => (string) $report->selected_violation_type,
            'latitude' => $this->decimal($report->latitude, 8),
            'longitude' => $this->decimal($report->longitude, 8),
            'gps_accuracy' => $report->gps_accuracy !== null
                ? $this->decimal($report->gps_accuracy, 2)
                : null,
            'timestamp' => $this->timestamp($report->timestamp),
            'contact_number' => $report->contact_number,
        ]);
    }

    private function decimal(mixed $value, int $places): string
    {
        return number_format((float) $value, $places, '.', '');
    }

    private function timestamp(mixed $value): string
    {
        return CarbonImmutable::parse($value)->utc()->format('Y-m-d\TH:i:s\Z');
    }

    private function hash(array $payload): string
    {
        return hash('sha256', json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ));
    }
}
