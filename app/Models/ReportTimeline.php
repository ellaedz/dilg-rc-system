<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportTimeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'status',
        'old_status',
        'remarks',
        'action_taken',
        'assigned_personnel',
        'updated_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: Timeline belongs to a Violation Report
     */
    public function violationReport()
    {
        return $this->belongsTo(ViolationReport::class, 'report_id');
    }

    /**
     * Relationship: Timeline belongs to a User (who updated it)
     */
    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get status icon based on status
     */
    public function getStatusIconAttribute()
    {
        $icons = [
            'Submitted' => 'fa-upload',
            'For Verification' => 'fa-search',
            'Verified' => 'fa-shield-alt',
            'Assigned' => 'fa-user-plus',
            'In Progress' => 'fa-spinner',
            'Action Taken' => 'fa-check-square',
            'Resolved' => 'fa-check-circle',
            'Rejected' => 'fa-times-circle',
            'Closed' => 'fa-archive',
        ];

        return $icons[$this->status] ?? 'fa-circle';
    }

    /**
     * Get status color class
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'Submitted' => 'gray',
            'For Verification' => 'orange',
            'Verified' => 'blue',
            'Assigned' => 'purple',
            'In Progress' => 'yellow',
            'Action Taken' => 'cyan',
            'Resolved' => 'green',
            'Rejected' => 'red',
            'Closed' => 'dark-gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }
}
