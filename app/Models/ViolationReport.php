<?php

namespace App\Models;

use App\Services\ReportNumberService;
use App\Support\CitizenViolationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViolationReport extends Model
{
    use HasFactory;

    public const AI_STATUS_PENDING = 'pending';

    public const AI_STATUS_PROCESSING = 'processing';

    public const AI_STATUS_COMPLETED = 'completed';

    public const AI_STATUS_FAILED = 'failed';

    public const TASK_STATUS_NOT_STARTED = 'not_started';

    public const TASK_STATUS_CREATING = 'creating';

    public const TASK_STATUS_CREATED = 'created';

    public const TASK_STATUS_FAILED = 'failed';

    public const TASK_STATUS_UNCERTAIN = 'uncertain';

    public const PHOTO_STATUS_NOT_PROVIDED = 'not_provided';

    public const PHOTO_STATUS_PENDING = 'pending';

    public const PHOTO_STATUS_PROCESSING = 'processing';

    public const PHOTO_STATUS_UPLOADED = 'uploaded';

    public const PHOTO_STATUS_FAILED_VALIDATION = 'failed_validation';

    public const PHOTO_STATUS_FAILED_STORAGE = 'failed_storage';

    protected static function booted(): void
    {
        static::saving(function (ViolationReport $report): void {
            if (! $report->exists
                && (! $report->verification_status || $report->verification_status === 'Unverified')) {
                $report->verification_status = 'Pending';
            }

            if ($report->isDirty('status')) {
                $report->report_status = $report->status;
            } elseif ($report->isDirty('report_status')) {
                $report->status = $report->report_status;
            }

            if (! $report->report_number && $report->report_id) {
                $report->report_number = $report->report_id;
            }

            if (! $report->report_id && $report->report_number) {
                $report->report_id = $report->report_number;
            }
        });
    }

    protected $fillable = [
        'report_id',
        'report_number',
        'token_derivation_nonce',
        'tracking_token_hash',
        'idempotency_key_hash',
        'submission_payload_hash',
        'submitted_by',
        'contact_number',
        'description',
        'image_path', // Changed from photo_path for consistency
        'photo_object_key',
        'photo_pending_object_key',
        'photo_storage_disk',
        'photo_mime_type',
        'photo_size_bytes',
        'photo_width',
        'photo_height',
        'photo_sha256',
        'latitude',
        'longitude',
        'gps_accuracy',
        'timestamp',
        'citizen_reported_barangay',
        'ai_training_consent',
        'ai_training_consent_at',
        'ai_training_notice_version',
        'selected_violation_type',
        'predicted_violation_category',
        'confidence_score',
        'image_validation_status',
        'image_model_version',
        'needs_manual_review',
        'text_prediction',
        'text_confidence',
        'final_ai_prediction',
        'final_ai_confidence',
        'ai_decision_source',
        'ai_needs_manual_review',
        'ai_processing_status',
        'ai_processing_attempts',
        'ai_request_id',
        'ai_processing_token_hash',
        'ai_processing_started_at',
        'ai_processing_expires_at',
        'ai_last_attempted_at',
        'ai_processed_at',
        'ai_model_version',
        'ai_image_prediction',
        'ai_image_confidence',
        'ai_image_status',
        'ai_image_detections',
        'ai_gis_result',
        'ai_model_metadata',
        'ai_timing',
        'ai_raw_response',
        'detected_barangay',
        'assigned_barangay_office',
        'location_context',
        'municipality_validated',
        'municipality_name',
        'barangay_detection_status',
        'needs_manual_barangay_review',
        'manually_assigned_barangay',
        'manual_assignment_reason',
        'manual_assignment_by',
        'manual_assignment_at',
        'status',
        'report_status',
        'verification_status',
        'legacy_verification_status',
        'photo_upload_status',
        'photo_upload_attempts',
        'photo_upload_error_code',
        'photo_upload_error_message',
        'photo_uploaded_at',
        'photo_processing_token_hash',
        'photo_processing_started_at',
        'photo_processing_expires_at',
        'photo_compensation_status',
        'task_creation_status',
        'task_generation',
        'task_creation_attempts',
        'task_id_hash',
        'task_creation_token_hash',
        'task_creation_started_at',
        'task_creation_expires_at',
        'task_last_attempted_at',
        'task_created_at',
        'task_creation_error_code',
        'task_creation_error_message',
        'barangay_assignment_status',
        'ai_manual_review_reason',
        'ai_manual_review_reasons',
        'processing_error_code',
        'processing_error_message',
        'ai_possible_violation',
        'ai_possible_violation_confidence',
        'official_violation_type',
        'verified_by',
        'verified_at',
        'ai_prediction_at_verification',
        'ai_confidence_at_verification',
        'staff_agreed_with_ai',
        'staff_verification_reason',
        'is_duplicate',
        'is_test_data',
        'processed_at',
        'assigned_personnel',
        'action_taken',
        'response_started_at',
        'resolved_at',
        'response_time_hours',
        'remarks',
        'date_submitted',
        'date_updated',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'response_started_at' => 'datetime',
        'resolved_at' => 'datetime',
        'date_submitted' => 'date',
        'date_updated' => 'date',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'gps_accuracy' => 'decimal:2',
        'confidence_score' => 'decimal:2',
        'needs_manual_review' => 'boolean',
        'text_confidence' => 'decimal:4',
        'final_ai_confidence' => 'decimal:4',
        'ai_needs_manual_review' => 'boolean',
        'ai_processing_attempts' => 'integer',
        'task_generation' => 'integer',
        'task_creation_attempts' => 'integer',
        'task_creation_started_at' => 'datetime',
        'task_creation_expires_at' => 'datetime',
        'task_last_attempted_at' => 'datetime',
        'task_created_at' => 'datetime',
        'ai_processing_started_at' => 'datetime',
        'ai_processing_expires_at' => 'datetime',
        'ai_last_attempted_at' => 'datetime',
        'ai_processed_at' => 'datetime',
        'ai_image_confidence' => 'decimal:6',
        'ai_image_detections' => 'array',
        'ai_gis_result' => 'array',
        'ai_model_metadata' => 'array',
        'ai_timing' => 'array',
        'ai_manual_review_reasons' => 'array',
        'ai_raw_response' => 'array',
        'ai_possible_violation_confidence' => 'decimal:4',
        'verified_at' => 'datetime',
        'ai_confidence_at_verification' => 'decimal:4',
        'staff_agreed_with_ai' => 'boolean',
        'is_duplicate' => 'boolean',
        'is_test_data' => 'boolean',
        'processed_at' => 'datetime',
        'response_time_hours' => 'decimal:2',
        'municipality_validated' => 'boolean',
        'needs_manual_barangay_review' => 'boolean',
        'ai_training_consent' => 'boolean',
        'ai_training_consent_at' => 'datetime',
        'manual_assignment_at' => 'datetime',
        'photo_uploaded_at' => 'datetime',
        'photo_size_bytes' => 'integer',
        'photo_width' => 'integer',
        'photo_height' => 'integer',
        'photo_processing_started_at' => 'datetime',
        'photo_processing_expires_at' => 'datetime',
    ];

    protected $hidden = [
        'token_derivation_nonce',
        'tracking_token_hash',
        'idempotency_key_hash',
        'submission_payload_hash',
        'image_path',
        'photo_object_key',
        'photo_pending_object_key',
        'photo_storage_disk',
        'photo_sha256',
        'photo_upload_attempts',
        'photo_processing_token_hash',
        'photo_processing_started_at',
        'photo_processing_expires_at',
        'photo_compensation_status',
        'photo_upload_error_message',
        'ai_processing_attempts',
        'ai_request_id',
        'ai_processing_token_hash',
        'ai_processing_started_at',
        'ai_processing_expires_at',
        'ai_last_attempted_at',
        'task_generation',
        'task_creation_attempts',
        'task_id_hash',
        'task_creation_token_hash',
        'task_creation_started_at',
        'task_creation_expires_at',
        'task_last_attempted_at',
        'task_created_at',
        'task_creation_error_code',
        'task_creation_error_message',
    ];

    /**
     * Generate unique report ID
     */
    public static function generateReportId()
    {
        return app(ReportNumberService::class)->next();
    }

    /**
     * Relationship: Violation Report has many Timeline entries
     */
    public function timelines()
    {
        return $this->hasMany(ReportTimeline::class, 'report_id')->orderBy('created_at', 'asc');
    }

    public function manualAssignmentBy()
    {
        return $this->belongsTo(User::class, 'manual_assignment_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Staff corrections take precedence, followed by the citizen's required
     * barangay selection. Polygon detection remains the fallback for older reports.
     */
    public function getEffectiveBarangayAttribute(): ?string
    {
        return $this->manually_assigned_barangay
            ?: $this->citizen_reported_barangay
            ?: $this->detected_barangay;
    }

    public function getCitizenSelectedViolationTypeAttribute(): ?string
    {
        return CitizenViolationType::citizenSelection($this->selected_violation_type);
    }

    public function getHasCitizenClassificationAttribute(): bool
    {
        return CitizenViolationType::hasCitizenClassification($this->selected_violation_type);
    }

    public function getCitizenViolationTypeLabelAttribute(): string
    {
        return CitizenViolationType::staffLabel($this->selected_violation_type);
    }

    public function scopeCitizenClassified($query)
    {
        return $query->where(
            'selected_violation_type',
            '!=',
            CitizenViolationType::UNCLASSIFIED
        );
    }

    public function scopeForEffectiveBarangay($query, string $barangay)
    {
        return $query->where(function ($builder) use ($barangay) {
            $builder->where('manually_assigned_barangay', $barangay)
                ->orWhere(function ($fallback) use ($barangay) {
                    $fallback->whereNull('manually_assigned_barangay')
                        ->where('citizen_reported_barangay', $barangay);
                })
                ->orWhere(function ($fallback) use ($barangay) {
                    $fallback->whereNull('manually_assigned_barangay')
                        ->whereNull('citizen_reported_barangay')
                        ->where('detected_barangay', $barangay);
                });
        });
    }

    public function scopeNeedsBarangayReview($query)
    {
        return $query->where('needs_manual_barangay_review', true)
            ->whereNull('manually_assigned_barangay');
    }

    /**
     * Records that are eligible for official municipal statistics.
     *
     * Operational maps intentionally remain broader than this scope.
     */
    public function scopeOfficialStatistics($query)
    {
        return $query
            ->where('is_test_data', false)
            ->whereNotNull('verified_at')
            ->whereNotNull('official_violation_type')
            ->where('verification_status', 'Valid Violation')
            ->where('municipality_validated', true)
            ->where('report_status', '!=', 'Rejected')
            ->where('is_duplicate', false);
    }

    public function getIsOfficialStatisticAttribute(): bool
    {
        return ! $this->is_test_data
            && $this->verified_at !== null
            && filled($this->official_violation_type)
            && $this->verification_status === 'Valid Violation'
            && $this->municipality_validated
            && $this->report_status !== 'Rejected'
            && ! $this->is_duplicate;
    }

    public function getOperationalMapStateAttribute(): string
    {
        if ($this->is_test_data) {
            return 'test_data';
        }

        if (! $this->municipality_validated
            || $this->barangay_assignment_status === 'outside_coverage'
            || $this->verification_status === 'Outside Jurisdiction') {
            return 'outside_jurisdiction';
        }

        if ($this->is_duplicate || $this->verification_status === 'Duplicate') {
            return 'duplicate';
        }

        if ($this->status === 'Rejected'
            || in_array($this->verification_status, ['Invalid Report', 'Insufficient Evidence'], true)) {
            return 'rejected';
        }

        if ($this->is_official_statistic) {
            return 'verified_valid';
        }

        if (in_array($this->ai_processing_status, [self::AI_STATUS_PENDING, self::AI_STATUS_PROCESSING], true)) {
            return 'ai_pending';
        }

        return 'pending_verification';
    }

    public function getOperationalMapStateLabelAttribute(): string
    {
        return match ($this->operational_map_state) {
            'verified_valid' => 'Staff-verified valid violation',
            'ai_pending' => 'AI analysis pending',
            'rejected' => 'Rejected or invalid report',
            'duplicate' => 'Duplicate report',
            'outside_jurisdiction' => 'Outside supported jurisdiction',
            'test_data' => 'Test data',
            default => 'Awaiting staff verification',
        };
    }
}
