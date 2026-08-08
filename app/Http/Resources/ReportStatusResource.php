<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'report_number' => $this->report_number,
            'tracking_id' => $this->report_number,
            'citizen_selected_violation_type' => $this->citizen_selected_violation_type,
            'has_citizen_classification' => $this->has_citizen_classification,
            'current_status' => $this->report_status,
            'verification_status' => $this->verification_status,
            'municipality_name' => $this->municipality_name,
            'barangay' => $this->effective_barangay,
            'barangay_detection_status' => $this->barangay_detection_status,
            'needs_manual_barangay_review' => (bool) $this->needs_manual_barangay_review,
            'image_prediction' => $this->predicted_violation_category,
            'ai_processing_status' => $this->ai_processing_status,
            'final_ai_category' => $this->ai_possible_violation,
            'final_ai_confidence' => $this->ai_possible_violation_confidence !== null
                ? (float) $this->ai_possible_violation_confidence
                : null,
            'ai_needs_manual_review' => (bool) $this->ai_needs_manual_review,
            'photo_upload_status' => $this->photo_upload_status,
            'photo_available' => $this->photo_upload_status === 'uploaded',
            'routing_status' => $this->barangay_assignment_status,
            'assigned_barangay_office' => $this->assigned_barangay_office,
            'latest_action' => $this->latest_public_action,
            'last_updated' => $this->updated_at?->toISOString(),
            'date_submitted' => $this->date_submitted?->toDateString(),
            'timeline' => $this->timelines->map(fn ($timeline) => [
                'status' => $timeline->status,
                'action' => $timeline->action_taken ?: $timeline->status,
                'updated_at' => $timeline->created_at?->toISOString(),
            ])->values(),
        ];
    }
}
