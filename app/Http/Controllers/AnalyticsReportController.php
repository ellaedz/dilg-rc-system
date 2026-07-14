<?php

namespace App\Http\Controllers;

use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsReportController extends Controller
{
    /**
     * Display DILG-wide road clearing analytics dashboard for Santa Cruz, Laguna
     */
    public function index()
    {
        // ======================================
        // STATISTICS CARDS
        // ======================================
        
        // 1. Total Road Clearing Reports
        $totalReports = ViolationReport::count();
        
        // 2. Total Barangays Monitored
        $totalBarangays = ViolationReport::whereNotNull('detected_barangay')
            ->where('detected_barangay', '!=', 'Location Not Available')
            ->where('detected_barangay', '!=', 'Outside Santa Cruz Coverage')
            ->distinct('detected_barangay')
            ->count('detected_barangay');
        
        // 3. Pending Verification
        $pendingVerification = ViolationReport::whereIn('status', ['Submitted', 'For Verification'])
            ->count();
        
        // 4. Verified Violations
        $verifiedViolations = ViolationReport::where('verification_status', 'Valid Violation')
            ->count();
        
        // 5. In Progress Reports
        $inProgressReports = ViolationReport::whereIn('status', ['Assigned', 'In Progress'])
            ->count();
        
        // 6. Resolved Reports
        $resolvedReports = ViolationReport::where('status', 'Resolved')->count();
        
        // 7. Rejected Reports
        $rejectedReports = ViolationReport::whereIn('status', ['Rejected'])
            ->orWhere('verification_status', 'Invalid Report')
            ->count();
        
        // 8. Average Response Time (placeholder calculation)
        $avgResponseTime = ViolationReport::whereNotNull('response_time_hours')
            ->avg('response_time_hours');
        $avgResponseTime = $avgResponseTime ? round($avgResponseTime, 1) : 0;
        
        // 9. Top Performing Barangay
        $topPerformingBarangay = ViolationReport::select('detected_barangay', 
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "Resolved" THEN 1 ELSE 0 END) as resolved'))
            ->whereNotNull('detected_barangay')
            ->where('detected_barangay', '!=', 'Location Not Available')
            ->where('detected_barangay', '!=', 'Outside Santa Cruz Coverage')
            ->groupBy('detected_barangay')
            ->havingRaw('total > 0')
            ->orderByRaw('(resolved / total) DESC')
            ->orderBy('resolved', 'DESC')
            ->first();
        
        // 10. Barangay Needing Attention
        $barangayNeedingAttention = ViolationReport::select('detected_barangay',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status IN ("Submitted", "For Verification") THEN 1 ELSE 0 END) as pending'))
            ->whereNotNull('detected_barangay')
            ->where('detected_barangay', '!=', 'Location Not Available')
            ->where('detected_barangay', '!=', 'Outside Santa Cruz Coverage')
            ->groupBy('detected_barangay')
            ->havingRaw('pending > 0')
            ->orderBy('pending', 'DESC')
            ->first();
        
        // ======================================
        // CHART DATA
        // ======================================
        
        // 1. Reports by Barangay
        $reportsByBarangay = ViolationReport::select('detected_barangay', DB::raw('COUNT(*) as count'))
            ->whereNotNull('detected_barangay')
            ->where('detected_barangay', '!=', 'Location Not Available')
            ->where('detected_barangay', '!=', 'Outside Santa Cruz Coverage')
            ->groupBy('detected_barangay')
            ->orderBy('count', 'DESC')
            ->get();
        
        // 2. Reports by Violation Type
        $reportsByViolationType = ViolationReport::select('selected_violation_type', DB::raw('COUNT(*) as count'))
            ->groupBy('selected_violation_type')
            ->orderBy('count', 'DESC')
            ->get();
        
        // 3. Reports by Status
        $reportsByStatus = ViolationReport::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->sortBy(function($item) {
                $order = ['Submitted' => 1, 'For Verification' => 2, 'Verified' => 3, 'Assigned' => 4, 'In Progress' => 5, 'Action Taken' => 6, 'Resolved' => 7, 'Rejected' => 8, 'Closed' => 9];
                return $order[$item->status] ?? 999;
            })
            ->values();
        
        // 4. Monthly Report Trend (last 6 months)
        $monthlyTrend = ViolationReport::select(
                DB::raw("strftime('%Y', created_at) as year"),
                DB::raw("strftime('%m', created_at) as month"),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('year', 'month')
            ->orderBy('year', 'ASC')
            ->orderBy('month', 'ASC')
            ->get()
            ->map(function($item) {
                $item->month_name = date('M', mktime(0, 0, 0, $item->month, 1));
                return $item;
            });
        
        // 5. Resolved vs Pending
        $resolvedVsPending = [
            'resolved' => $resolvedReports,
            'pending' => $pendingVerification + $inProgressReports
        ];
        
        // 6. Response Time by Barangay
        $responseTimeByBarangay = ViolationReport::select('detected_barangay',
                DB::raw('AVG(response_time_hours) as avg_response_time'),
                DB::raw('COUNT(*) as report_count'))
            ->whereNotNull('detected_barangay')
            ->whereNotNull('response_time_hours')
            ->where('detected_barangay', '!=', 'Location Not Available')
            ->where('detected_barangay', '!=', 'Outside Santa Cruz Coverage')
            ->groupBy('detected_barangay')
            ->orderBy('avg_response_time', 'ASC')
            ->get()
            ->map(function($item) {
                $item->avg_response_time = round($item->avg_response_time, 1);
                return $item;
            });
        
        // 7. Top Recurring Violation Type
        $topRecurringViolationType = $reportsByViolationType->first();
        
        // ======================================
        // PACKAGE DATA FOR VIEW
        // ======================================
        
        $stats = [
            'total_reports' => $totalReports,
            'total_barangays' => $totalBarangays,
            'pending_verification' => $pendingVerification,
            'verified_violations' => $verifiedViolations,
            'in_progress' => $inProgressReports,
            'resolved' => $resolvedReports,
            'rejected' => $rejectedReports,
            'avg_response_time' => $avgResponseTime,
            'top_performing_barangay' => $topPerformingBarangay ? $topPerformingBarangay->detected_barangay : 'N/A',
            'top_performing_resolved' => $topPerformingBarangay ? $topPerformingBarangay->resolved : 0,
            'barangay_needing_attention' => $barangayNeedingAttention ? $barangayNeedingAttention->detected_barangay : 'N/A',
            'barangay_pending_count' => $barangayNeedingAttention ? $barangayNeedingAttention->pending : 0,
            'top_recurring_violation' => $topRecurringViolationType ? $topRecurringViolationType->selected_violation_type : 'N/A',
            'top_recurring_count' => $topRecurringViolationType ? $topRecurringViolationType->count : 0,
        ];
        
        return view('analytics-reports.index', compact(
            'stats',
            'reportsByBarangay',
            'reportsByViolationType',
            'reportsByStatus',
            'monthlyTrend',
            'resolvedVsPending',
            'responseTimeByBarangay'
        ));
    }

    /**
     * Export placeholder
     */
    public function export(Request $request)
    {
        return back()->with('info', 'Export functionality will be implemented in a later phase.');
    }

    /**
     * Print DILG-wide analytics report
     */
    public function print()
    {
        // Collect all statistics for printable report
        $totalReports = ViolationReport::count();
        
        $totalBarangays = ViolationReport::whereNotNull('detected_barangay')
            ->where('detected_barangay', '!=', 'Location Not Available')
            ->where('detected_barangay', '!=', 'Outside Santa Cruz Coverage')
            ->distinct('detected_barangay')
            ->count('detected_barangay');
        
        $pendingVerification = ViolationReport::whereIn('status', ['Submitted', 'For Verification'])->count();
        $verifiedViolations = ViolationReport::where('verification_status', 'Valid Violation')->count();
        $inProgressReports = ViolationReport::whereIn('status', ['Assigned', 'In Progress'])->count();
        $actionTakenReports = ViolationReport::where('status', 'Action Taken')->count();
        $resolvedReports = ViolationReport::where('status', 'Resolved')->count();
        $rejectedReports = ViolationReport::where('status', 'Rejected')->count();
        
        $avgResponseTime = ViolationReport::whereNotNull('response_time_hours')
            ->avg('response_time_hours');
        $avgResponseTime = $avgResponseTime ? round($avgResponseTime, 1) : 0;
        
        // Violation Type Summary
        $reportsByViolationType = ViolationReport::select('selected_violation_type', DB::raw('COUNT(*) as count'))
            ->groupBy('selected_violation_type')
            ->orderBy('count', 'DESC')
            ->get();
        
        // Barangay Summary Table
        $barangaySummary = ViolationReport::select('detected_barangay',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN verification_status = "Valid Violation" THEN 1 ELSE 0 END) as verified'),
                DB::raw('SUM(CASE WHEN status IN ("Assigned", "In Progress") THEN 1 ELSE 0 END) as in_progress'),
                DB::raw('SUM(CASE WHEN status = "Resolved" THEN 1 ELSE 0 END) as resolved'),
                DB::raw('SUM(CASE WHEN status IN ("Submitted", "For Verification") THEN 1 ELSE 0 END) as pending'),
                DB::raw('AVG(CASE WHEN response_time_hours IS NOT NULL THEN response_time_hours ELSE NULL END) as avg_response_time'))
            ->whereNotNull('detected_barangay')
            ->where('detected_barangay', '!=', 'Location Not Available')
            ->where('detected_barangay', '!=', 'Outside Santa Cruz Coverage')
            ->groupBy('detected_barangay')
            ->orderBy('total', 'DESC')
            ->get()
            ->map(function($item) {
                $item->avg_response_time = $item->avg_response_time ? round($item->avg_response_time, 1) : 0;
                $item->resolution_rate = $item->total > 0 ? round(($item->resolved / $item->total) * 100, 1) : 0;
                // Simple performance score
                $item->performance_score = $item->resolution_rate;
                return $item;
            });
        
        // Status Summary Table
        $statusSummary = ViolationReport::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(function($item) use ($totalReports) {
                $item->percentage = $totalReports > 0 ? round(($item->count / $totalReports) * 100, 1) : 0;
                return $item;
            });
        
        // Top Performing Barangay
        $topPerformingBarangay = $barangaySummary->sortByDesc('performance_score')->first();
        
        // Barangay Needing Attention
        $barangayNeedingAttention = $barangaySummary->sortByDesc('pending')->first();
        
        $stats = [
            'total_reports' => $totalReports,
            'total_barangays' => $totalBarangays,
            'pending_verification' => $pendingVerification,
            'verified_violations' => $verifiedViolations,
            'in_progress' => $inProgressReports,
            'action_taken' => $actionTakenReports,
            'resolved' => $resolvedReports,
            'rejected' => $rejectedReports,
            'avg_response_time' => $avgResponseTime,
        ];
        
        return view('analytics-reports.print', compact(
            'stats',
            'reportsByViolationType',
            'barangaySummary',
            'statusSummary',
            'topPerformingBarangay',
            'barangayNeedingAttention'
        ));
    }
}
