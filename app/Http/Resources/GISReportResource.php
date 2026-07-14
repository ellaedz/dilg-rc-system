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
            'selected_violation_type' => $this->selected_violation_type,
            'status' => $this->status,
            'verification_status' => $this->verification_status,
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
