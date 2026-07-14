<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobileReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'report_id' => $this->report_id,
            'tracking_id' => $this->report_id,
            'selected_violation_type' => $this->selected_violation_type,
            'status' => $this->status,
            'verification_status' => $this->verification_status,
            'is_inside_santa_cruz' => (bool) $this->municipality_validated,
            'municipality_name' => $this->municipality_name,
            'detected_barangay' => $this->detected_barangay,
            'barangay_detection_status' => $this->barangay_detection_status,
            'needs_manual_barangay_review' => (bool) $this->needs_manual_barangay_review,
            'assigned_barangay_office' => $this->assigned_barangay_office,
            'location_context' => $this->location_context,
            'note' => 'Please save your Tracking ID to check the status of your report.',
            'description' => $this->description,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'gps_accuracy' => $this->gps_accuracy !== null ? (float) $this->gps_accuracy : null,
            'image_url' => $this->image_path ? asset('storage/'.$this->image_path) : null,
            'timestamp' => $this->timestamp?->toISOString(),
            'date_submitted' => $this->date_submitted?->toDateString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
