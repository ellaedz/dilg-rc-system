<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GISReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'report_id' => $this->report_id,
            'tracking_id' => $this->report_id,
            'details_url' => route('violation-reports.show', $this->resource),
            'selected_violation_type' => $this->citizen_selected_violation_type,
            'citizen_selected_violation_type' => $this->citizen_selected_violation_type,
            'has_citizen_classification' => $this->has_citizen_classification,
            'status' => $this->status,
            'verification_status' => $this->verification_status,
            'operational_state' => $this->operational_map_state,
            'operational_state_label' => $this->operational_map_state_label,
            'is_official_statistic' => $this->is_official_statistic,
            'official_violation_type' => $this->official_violation_type,
            'is_duplicate' => (bool) $this->is_duplicate,
            'is_test_data' => (bool) $this->is_test_data,
            'ai_processing_status' => $this->ai_processing_status,
            'detected_barangay' => $this->detected_barangay,
            'manually_assigned_barangay' => $this->manually_assigned_barangay,
            'effective_barangay' => $this->effective_barangay,
            'barangay_detection_status' => $this->barangay_detection_status,
            'needs_manual_barangay_review' => (bool) $this->needs_manual_barangay_review,
            'municipality_validated' => (bool) $this->municipality_validated,
            'assigned_barangay_office' => $this->assigned_barangay_office,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'timestamp' => ($this->timestamp ?: $this->created_at)?->format('Y-m-d H:i:s'),
            'location_context' => $this->location_context,
        ];
    }
}
