<?php

namespace App\Http\Controllers;

use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use Illuminate\Http\Request;

class BarangayDashboardController extends Controller
{
    /**
     * Barangay-specific dashboard - Shows only reports for selected barangay
     */
    public function index($barangay)
    {
        // Verify barangay exists
        $barangayDetails = BarangayAssignmentService::getBarangayByName($barangay);
        if (!$barangayDetails) {
            abort(404, 'Barangay not found');
        }

        // Statistics for this barangay only
        $stats = [
            'barangay_name' => $barangay,
            'total_reports' => ViolationReport::forEffectiveBarangay($barangay)->count(),
            'new_reports' => ViolationReport::forEffectiveBarangay($barangay)
                ->where('status', 'Submitted')->count(),
            'verified_reports' => ViolationReport::forEffectiveBarangay($barangay)
                ->where('status', 'Verified')->count(),
            'in_progress' => ViolationReport::forEffectiveBarangay($barangay)
                ->whereIn('status', ['Assigned', 'In Progress'])->count(),
            'resolved_reports' => ViolationReport::forEffectiveBarangay($barangay)
                ->where('status', 'Resolved')->count(),
        ];

        // Calculate average response time for resolved reports
        $resolvedReports = ViolationReport::forEffectiveBarangay($barangay)
            ->where('status', 'Resolved')
            ->whereNotNull('response_time_hours')
            ->get();

        $stats['avg_response_time'] = $resolvedReports->isEmpty() 
            ? 'N/A' 
            : round($resolvedReports->avg('response_time_hours'), 1) . ' hours';

        // Recent reports for this barangay
        $recentReports = ViolationReport::forEffectiveBarangay($barangay)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Reports by violation type for this barangay
        $reportsByType = ViolationReport::forEffectiveBarangay($barangay)
            ->selectRaw('selected_violation_type, COUNT(*) as count')
            ->groupBy('selected_violation_type')
            ->orderBy('count', 'desc')
            ->get();

        // Reports by status for this barangay
        $reportsByStatus = ViolationReport::forEffectiveBarangay($barangay)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->orderBy('count', 'desc')
            ->get();

        return view('barangay.dashboard', compact('stats', 'recentReports', 'reportsByType', 'reportsByStatus', 'barangay'));
    }

    /**
     * AJAX: Get real-time barangay dashboard statistics
     */
    public function getStats($barangay)
    {
        $stats = [
            'total_reports' => ViolationReport::forEffectiveBarangay($barangay)->count(),
            'new_reports' => ViolationReport::forEffectiveBarangay($barangay)->where('status', 'Submitted')->count(),
            'verified_reports' => ViolationReport::forEffectiveBarangay($barangay)->where('status', 'Verified')->count(),
            'in_progress' => ViolationReport::forEffectiveBarangay($barangay)->whereIn('status', ['Assigned', 'In Progress'])->count(),
            'resolved_reports' => ViolationReport::forEffectiveBarangay($barangay)->where('status', 'Resolved')->count(),
        ];

        return response()->json($stats);
    }
}
