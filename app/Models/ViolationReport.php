<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViolationReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'submitted_by',
        'contact_number',
        'description',
        'image_path', // Changed from photo_path for consistency
        'latitude',
        'longitude',
        'gps_accuracy',
        'timestamp',
        'selected_violation_type',
        'predicted_violation_category',
        'confidence_score',
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
        'verification_status',
        'assigned_personnel',
        'action_taken',
        'response_started_at',
        'resolved_at',
        'response_time_hours',
        'remarks',
        'date_submitted',
        'date_updated'
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
        'response_time_hours' => 'decimal:2',
        'municipality_validated' => 'boolean',
        'needs_manual_barangay_review' => 'boolean',
        'manual_assignment_at' => 'datetime',
    ];

    /**
     * Generate unique report ID
     */
    public static function generateReportId()
    {
        $year = date('Y');
        $lastReport = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $number = $lastReport ? (int) substr($lastReport->report_id, -4) + 1 : 1;
        
        return 'RCV-' . $year . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
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

    /**
     * A polygon detection takes precedence over the temporary DILG route.
     */
    public function getEffectiveBarangayAttribute(): ?string
    {
        return $this->detected_barangay ?: $this->manually_assigned_barangay;
    }

    public function scopeForEffectiveBarangay($query, string $barangay)
    {
        return $query->where(function ($builder) use ($barangay) {
            $builder->where('detected_barangay', $barangay)
                ->orWhere(function ($fallback) use ($barangay) {
                    $fallback->whereNull('detected_barangay')
                        ->where('manually_assigned_barangay', $barangay);
                });
        });
    }

    public function scopeNeedsBarangayReview($query)
    {
        return $query->where('needs_manual_barangay_review', true)
            ->whereNull('manually_assigned_barangay');
    }
}
