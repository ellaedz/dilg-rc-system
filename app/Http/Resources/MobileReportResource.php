<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobileReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'report_number' => $this->report_number,
            'report_id' => $this->report_number,
            'selected_violation_type' => $this->citizen_selected_violation_type,
            'citizen_selected_violation_type' => $this->citizen_selected_violation_type,
            'has_citizen_classification' => $this->has_citizen_classification,
            'image_result' => $this->predicted_violation_category,
            'image_confidence' => $this->confidence_score !== null ? (float) $this->confidence_score : null,
            'image_validation_status' => $this->image_validation_status,
            'image_model_version' => $this->image_model_version,
            'needs_manual_review' => (bool) $this->needs_manual_review,
            'report_status' => $this->report_status,
            'ai_processing_status' => $this->ai_processing_status,
            'final_ai_category' => $this->ai_possible_violation,
            'final_ai_confidence' => $this->ai_possible_violation_confidence !== null
                ? (float) $this->ai_possible_violation_confidence
                : null,
            'ai_needs_manual_review' => (bool) $this->ai_needs_manual_review,
            'photo_upload_status' => $this->photo_upload_status,
            'photo_available' => $this->photo_upload_status === 'uploaded',
            'photo_error_code' => $this->photo_upload_error_code,
            'status' => $this->report_status,
            'verification_status' => $this->verification_status,
            'is_inside_santa_cruz' => (bool) $this->municipality_validated,
            'municipality_name' => $this->municipality_name,
            'detected_barangay' => $this->detected_barangay,
            'barangay_detection_status' => $this->barangay_detection_status,
            'barangay_assignment_status' => $this->barangay_assignment_status,
            'needs_manual_barangay_review' => (bool) $this->needs_manual_barangay_review,
            'assigned_barangay_office' => $this->assigned_barangay_office,
            'location_context' => $this->location_context,
            'note' => 'Please save your Tracking ID to check the status of your report.',
            'description' => $this->description,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'gps_accuracy' => $this->gps_accuracy !== null ? (float) $this->gps_accuracy : null,
            'timestamp' => $this->timestamp?->toISOString(),
            'date_submitted' => $this->date_submitted?->toDateString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
