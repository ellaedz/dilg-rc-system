<?php

namespace App\Http\Controllers;

use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Real-time road clearing violation statistics from database
        $stats = [
            'total_reports' => ViolationReport::count(),
            'new_submissions' => ViolationReport::where('status', 'Submitted')->count(),
            'for_verification' => ViolationReport::where('status', 'For Verification')->count(),
            'verified' => ViolationReport::where('status', 'Verified')->count(),
            'assigned' => ViolationReport::where('status', 'Assigned')->count(),
            'in_progress' => ViolationReport::where('status', 'In Progress')->count(),
            'action_taken' => ViolationReport::where('status', 'Action Taken')->count(),
            'resolved' => ViolationReport::where('status', 'Resolved')->count(),
            'rejected' => ViolationReport::where('status', 'Rejected')->count(),
            'reports_with_gps' => ViolationReport::whereNotNull('latitude')->whereNotNull('longitude')->count(),
            'reports_with_photo' => ViolationReport::whereNotNull('photo_path')->count(),
            'dataset_status' => 'Not Connected',
            'ai_model_status' => 'Not Connected',
            'gis_status' => 'Not Connected'
        ];

        // Top violation type
        $topViolationType = ViolationReport::selectRaw('selected_violation_type, COUNT(*) as count')
            ->groupBy('selected_violation_type')
            ->orderBy('count', 'desc')
            ->first();

        $stats['top_violation_type'] = $topViolationType ? $topViolationType->selected_violation_type : 'N/A';
        $stats['top_violation_count'] = $topViolationType ? $topViolationType->count : 0;

        // Top barangay
        $topBarangay = ViolationReport::selectRaw('detected_barangay, COUNT(*) as count')
            ->whereNotNull('detected_barangay')
            ->where('detected_barangay', '!=', 'Location Not Available')
            ->where('detected_barangay', '!=', 'Outside Covered Area')
            ->groupBy('detected_barangay')
            ->orderBy('count', 'desc')
            ->first();

        $stats['top_barangay'] = $topBarangay ? $topBarangay->detected_barangay : 'N/A';
        $stats['top_barangay_count'] = $topBarangay ? $topBarangay->count : 0;

        // Recent activities
        $recentActivities = ViolationReport::orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($report) {
                return [
                    'id' => $report->report_id,
                    'description' => $report->selected_violation_type . ' - ' . substr($report->description, 0, 50) . '...',
                    'barangay' => $report->detected_barangay ?? 'N/A',
                    'status' => $report->status,
                    'date' => $report->created_at->format('Y-m-d H:i')
                ];
            });

        // Reports by barangay (top 10)
        $reportsByBarangay = ViolationReport::selectRaw('detected_barangay, COUNT(*) as count')
            ->whereNotNull('detected_barangay')
            ->where('detected_barangay', '!=', 'Location Not Available')
            ->where('detected_barangay', '!=', 'Outside Covered Area')
            ->groupBy('detected_barangay')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Reports by violation type
        $reportsByType = ViolationReport::selectRaw('selected_violation_type, COUNT(*) as count')
            ->groupBy('selected_violation_type')
            ->orderBy('count', 'desc')
            ->get();

        return view('dashboard', compact('stats', 'recentActivities', 'reportsByBarangay', 'reportsByType'));
    }
}
