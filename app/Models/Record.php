<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    use HasFactory;

    protected $fillable = [
        'record_id',
        'full_name',
        'contact_number',
        'email',
        'address',
        'concern_type',
        'description',
        'photo_evidence',
        'latitude',
        'longitude',
        'gps_accuracy',
        'detected_barangay',
        'assigned_barangay_office',
        'location_name',
        'urgency_level',
        'date_submitted',
        'status',
        'assigned_office',
        'assigned_personnel',
        'remarks'
    ];

    protected $casts = [
        'date_submitted' => 'date',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'gps_accuracy' => 'decimal:2',
    ];

    public static function generateRecordId()
    {
        $year = date('Y');
        $lastRecord = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $number = $lastRecord ? (int) substr($lastRecord->record_id, -3) + 1 : 1;
        
        return 'REC-' . $year . '-' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
}
