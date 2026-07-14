<?php

namespace App\Http\Controllers;

use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarangayAnalyticsReportController extends Controller
{
    /**
     * Display barangay-specific analytics for transparency
     * Only shows reports for the selected barangay
     */
    public function index($barangay)
    {
        // Validate barangay exists in our system
        $allBarangays = BarangayAssignmentService::getAllBarangays();
        if (!in_array($barangay, $allBarangays)) {
            abort(404, 'Barangay not found');
        }
        
        // ======================================
        // BARANGAY STATISTICS CARDS
        // ======================================
        
        // Base query - ONLY reports for this barangay
        $baseQuery = ViolationReport::forEffectiveBarangay($barangay);
        
        // 1. Total Reports in Barangay
        $totalReports = (clone $baseQuery)->count();
        
        // 2. New Reports (Submitted)
        $newReports = (clone $baseQuery)->where('status', 'Submitted')->count();
        
        // 3. For Verification
        $forVerification = (clone $baseQuery)->where('status', 'For Verification')->count();
        
        // 4. Verified Reports
        $verifiedReports = (clone $baseQuery)->where('verification_status', 'Valid Violation')->count();
        
        // 5. In Progress
        $inProgress = (clone $baseQuery)->whereIn('status', ['Assigned', 'In Progress'])->count();
        
        // 6. Action Taken
        $actionTaken = (clone $baseQuery)->where('status', 'Action Taken')->count();
        
        // 7. Resolved
        $resolved = (clone $baseQuery)->where('status', 'Resolved')->count();
        
        // 8. Average Response Time
        $avgResponseTime = (clone $baseQuery)
            ->whereNotNull('response_time_hours')
            ->avg('response_time_hours');
        $avgResponseTime = $avgResponseTime ? round($avgResponseTime, 1) : 0;
        
        // ======================================
        // BARANGAY VISUAL SECTIONS
        // ======================================
        
        // 1. Barangay Reports by Status
        $reportsByStatus = ViolationReport::select('status', DB::raw('COUNT(*) as count'))
            ->forEffectiveBarangay($barangay)
            ->groupBy('status')
            ->get()
            ->sortBy(function($item) {
                $order = ['Submitted' => 1, 'For Verification' => 2, 'Verified' => 3, 'Assigned' => 4, 'In Progress' => 5, 'Action Taken' => 6, 'Resolved' => 7, 'Rejected' => 8, 'Closed' => 9];
                return $order[$item->status] ?? 999;
            })
            ->values();
        
        // 2. Barangay Reports by Violation Type
        $reportsByViolationType = ViolationReport::select('selected_violation_type', DB::raw('COUNT(*) as count'))
            ->forEffectiveBarangay($barangay)
            ->groupBy('selected_violation_type')
            ->orderBy('count', 'DESC')
            ->get();
        
        // 3. Monthly Barangay Trend (last 6 months)
        $monthlyTrend = ViolationReport::select(
                DB::raw("strftime('%Y', created_at) as year"),
                DB::raw("strftime('%m', created_at) as month"),
                DB::raw('COUNT(*) as count')
            )
            ->forEffectiveBarangay($barangay)
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('year', 'month')
            ->orderBy('year', 'ASC')
            ->orderBy('month', 'ASC')
            ->get()
            ->map(function($item) {
                $item->month_name = date('M', mktime(0, 0, 0, $item->month, 1));
                return $item;
            });
        
        // 4. Response Timeline Summary - Recent reports with status updates
        $recentReports = ViolationReport::forEffectiveBarangay($barangay)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get();
        
        // Calculate resolution rate
        $resolutionRate = $totalReports > 0 ? round(($resolved / $totalReports) * 100, 1) : 0;
        
        // Calculate pending count
        $pendingReports = $newReports + $forVerification + $inProgress;
        
        // ======================================
        // PACKAGE DATA FOR VIEW
        // ======================================
        
        $stats = [
            'barangay' => $barangay,
            'total_reports' => $totalReports,
            'new_reports' => $newReports,
            'for_verification' => $forVerification,
            'verified_reports' => $verifiedReports,
            'in_progress' => $inProgress,
            'action_taken' => $actionTaken,
            'resolved' => $resolved,
            'avg_response_time' => $avgResponseTime,
            'resolution_rate' => $resolutionRate,
            'pending_reports' => $pendingReports,
        ];
        
        return view('barangay.analytics-reports', compact(
            'barangay',
            'stats',
            'reportsByStatus',
            'reportsByViolationType',
            'monthlyTrend',
            'recentReports'
        ));
    }

    /**
     * Print barangay-specific analytics report
     */
    public function print($barangay)
    {
        // Validate barangay exists
        $allBarangays = BarangayAssignmentService::getAllBarangays();
        if (!in_array($barangay, $allBarangays)) {
            abort(404, 'Barangay not found');
        }
        
        // Base query - ONLY reports for this barangay
        $baseQuery = ViolationReport::forEffectiveBarangay($barangay);
        
        // Statistics
        $totalReports = (clone $baseQuery)->count();
        $newReports = (clone $baseQuery)->where('status', 'Submitted')->count();
        $forVerification = (clone $baseQuery)->where('status', 'For Verification')->count();
        $verifiedReports = (clone $baseQuery)->where('verification_status', 'Valid Violation')->count();
        $inProgress = (clone $baseQuery)->whereIn('status', ['Assigned', 'In Progress'])->count();
        $actionTaken = (clone $baseQuery)->where('status', 'Action Taken')->count();
        $resolved = (clone $baseQuery)->where('status', 'Resolved')->count();
        $rejected = (clone $baseQuery)->where('status', 'Rejected')->count();
        
        $avgResponseTime = (clone $baseQuery)
            ->whereNotNull('response_time_hours')
            ->avg('response_time_hours');
        $avgResponseTime = $avgResponseTime ? round($avgResponseTime, 1) : 0;
        
        // Violation Type Summary
        $reportsByViolationType = ViolationReport::select('selected_violation_type', DB::raw('COUNT(*) as count'))
            ->forEffectiveBarangay($barangay)
            ->groupBy('selected_violation_type')
            ->orderBy('count', 'DESC')
            ->get();
        
        // Status Summary
        $statusSummary = ViolationReport::select('status', DB::raw('COUNT(*) as count'))
            ->forEffectiveBarangay($barangay)
            ->groupBy('status')
            ->get()
            ->map(function($item) use ($totalReports) {
                $item->percentage = $totalReports > 0 ? round(($item->count / $totalReports) * 100, 1) : 0;
                return $item;
            });
        
        // Recent Actions Taken
        $recentActions = ViolationReport::forEffectiveBarangay($barangay)
            ->whereNotNull('action_taken')
            ->orderBy('date_updated', 'DESC')
            ->limit(10)
            ->get();
        
        // Response Transparency Summary
        $waitingVerification = $forVerification;
        $inProgressCount = $inProgress;
        $resolvedCount = $resolved;
        $resolutionRate = $totalReports > 0 ? round(($resolved / $totalReports) * 100, 1) : 0;
        
        $stats = [
            'total_reports' => $totalReports,
            'new_reports' => $newReports,
            'for_verification' => $forVerification,
            'verified_reports' => $verifiedReports,
            'in_progress' => $inProgress,
            'action_taken' => $actionTaken,
            'resolved' => $resolved,
            'rejected' => $rejected,
            'avg_response_time' => $avgResponseTime,
            'waiting_verification' => $waitingVerification,
            'in_progress_count' => $inProgressCount,
            'resolved_count' => $resolvedCount,
            'resolution_rate' => $resolutionRate,
        ];
        
        return view('barangay.analytics-print', compact(
            'barangay',
            'stats',
            'reportsByViolationType',
            'statusSummary',
            'recentActions'
        ));
    }
}
