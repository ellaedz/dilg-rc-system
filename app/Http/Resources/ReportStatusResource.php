<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'tracking_id' => $this->report_id,
            'current_status' => $this->status,
            'verification_status' => $this->verification_status,
            'municipality_name' => $this->municipality_name,
            'barangay' => $this->effective_barangay,
            'barangay_detection_status' => $this->barangay_detection_status,
            'needs_manual_barangay_review' => (bool) $this->needs_manual_barangay_review,
            'routing_status' => $this->manually_assigned_barangay ? 'manually_routed' : $this->barangay_detection_status,
            'assigned_barangay_office' => $this->assigned_barangay_office,
            'latest_action' => $this->latest_public_action,
            'last_updated' => $this->updated_at?->toISOString(),
            'date_submitted' => $this->date_submitted?->toDateString(),
        ];
    }
}
