<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'complaint_id',
        'full_name',
        'contact_number',
        'address',
        'barangay',
        'municipality',
        'province',
        'concern_type',
        'subject',
        'description',
        'priority',
        'status',
        'assigned_office',
        'assigned_personnel',
        'remarks',
        'date_filed'
    ];

    protected $casts = [
        'date_filed' => 'date',
    ];

    public static function generateComplaintId()
    {
        $year = date('Y');
        $lastComplaint = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $number = $lastComplaint ? (int) substr($lastComplaint->complaint_id, -3) + 1 : 1;
        
        return 'CMP-' . $year . '-' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
}
