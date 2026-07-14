<?php

namespace App\Http\Controllers;

use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use Illuminate\Http\Request;

class DilgDashboardController extends Controller
{
    /**
     * DILG Admin Dashboard - View all barangays and reports
     */
    public function index()
    {
        // Overall statistics
        $stats = [
            'total_reports' => ViolationReport::count(),
            'total_barangays' => count(BarangayAssignmentService::getAllBarangays()),
            'new_reports' => ViolationReport::where('status', 'Submitted')->count(),
            'verified_reports' => ViolationReport::where('status', 'Verified')->count(),
            'resolved_reports' => ViolationReport::where('status', 'Resolved')->count(),
            'pending_reports' => ViolationReport::whereIn('status', ['Submitted', 'For Verification', 'Verified', 'Assigned', 'In Progress'])->count(),
            'needs_barangay_review' => ViolationReport::needsBarangayReview()->count(),
            'manually_routed' => ViolationReport::whereNotNull('manually_assigned_barangay')->count(),
        ];

        // Top performing barangay (highest resolution rate)
        $barangayStats = ViolationReport::selectRaw('
                COALESCE(detected_barangay, manually_assigned_barangay) as detected_barangay,
                COUNT(*) as total_reports,
                SUM(CASE WHEN status = "Resolved" THEN 1 ELSE 0 END) as resolved_count
            ')
            ->where(function ($query) {
                $query->whereNotNull('detected_barangay')->orWhereNotNull('manually_assigned_barangay');
            })
            ->groupByRaw('COALESCE(detected_barangay, manually_assigned_barangay)')
            ->get();

        // Calculate resolution rate in PHP
        $topBarangay = $barangayStats
            ->map(function($b) {
                $b->resolution_rate = $b->total_reports > 0 
                    ? round(($b->resolved_count / $b->total_reports) * 100, 2) 
                    : 0;
                return $b;
            })
            ->sortByDesc('resolution_rate')
            ->first();

        $stats['top_barangay'] = $topBarangay ? $topBarangay->detected_barangay : 'N/A';
        $stats['top_barangay_resolution_rate'] = $topBarangay ? $topBarangay->resolution_rate . '%' : '0%';

        // Barangay with most pending reports
        $mostPendingBarangay = ViolationReport::selectRaw('
                COALESCE(detected_barangay, manually_assigned_barangay) as detected_barangay,
                COUNT(*) as pending_count
            ')
            ->whereIn('status', ['Submitted', 'For Verification', 'Verified', 'Assigned', 'In Progress'])
            ->where(function ($query) {
                $query->whereNotNull('detected_barangay')->orWhereNotNull('manually_assigned_barangay');
            })
            ->groupByRaw('COALESCE(detected_barangay, manually_assigned_barangay)')
            ->orderBy('pending_count', 'desc')
            ->first();

        $stats['most_pending_barangay'] = $mostPendingBarangay ? $mostPendingBarangay->detected_barangay : 'N/A';
        $stats['most_pending_count'] = $mostPendingBarangay ? $mostPendingBarangay->pending_count : 0;

        // Recent reports from all barangays
        $recentReports = ViolationReport::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Reports by barangay
        $reportsByBarangay = ViolationReport::selectRaw('
                COALESCE(detected_barangay, manually_assigned_barangay) as detected_barangay,
                COUNT(*) as total_reports,
                SUM(CASE WHEN status = "Resolved" THEN 1 ELSE 0 END) as resolved_count,
                SUM(CASE WHEN status IN ("Submitted", "For Verification", "Verified", "Assigned", "In Progress") THEN 1 ELSE 0 END) as pending_count
            ')
            ->where(function ($query) {
                $query->whereNotNull('detected_barangay')->orWhereNotNull('manually_assigned_barangay');
            })
            ->groupByRaw('COALESCE(detected_barangay, manually_assigned_barangay)')
            ->orderBy('total_reports', 'desc')
            ->get();

        return view('dilg.dashboard', compact('stats', 'recentReports', 'reportsByBarangay'));
    }

    /**
     * AJAX: Get real-time dashboard statistics
     */
    public function getStats()
    {
        $stats = [
            'total_reports' => ViolationReport::count(),
            'new_reports' => ViolationReport::where('status', 'Submitted')->count(),
            'verified_reports' => ViolationReport::where('status', 'Verified')->count(),
            'resolved_reports' => ViolationReport::where('status', 'Resolved')->count(),
            'pending_reports' => ViolationReport::whereIn('status', ['Submitted', 'For Verification', 'Verified', 'Assigned', 'In Progress'])->count(),
            'needs_barangay_review' => ViolationReport::needsBarangayReview()->count(),
            'manually_routed' => ViolationReport::whereNotNull('manually_assigned_barangay')->count(),
        ];

        return response()->json($stats);
    }
}
