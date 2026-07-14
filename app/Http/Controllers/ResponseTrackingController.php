<?php

namespace App\Http\Controllers;

use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use Illuminate\Http\Request;

class ResponseTrackingController extends Controller
{
    /**
     * Display response tracking (transparency dashboard)
     */
    public function index(Request $request)
    {
        $query = ViolationReport::query();

        // Search
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('report_id', 'like', "%{$search}%")
                  ->orWhere('submitted_by', 'like', "%{$search}%");
            });
        }

        // Filter by barangay
        if ($request->has('barangay') && $request->barangay != '') {
            $query->where('detected_barangay', $request->barangay);
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $reports = $query->orderBy('date_updated', 'desc')
                        ->orderBy('created_at', 'desc')
                        ->paginate(15);
        
        $barangays = BarangayAssignmentService::getAllBarangays();

        // Calculate response time statistics
        $totalReports = ViolationReport::count();
        $pendingReports = ViolationReport::whereIn('status', ['Submitted', 'For Verification', 'Verified'])->count();
        $inProgressReports = ViolationReport::whereIn('status', ['Assigned', 'In Progress'])->count();
        $resolvedReports = ViolationReport::where('status', 'Resolved')->count();
        $avgResponseTime = $this->calculateAverageResponseDays();
        $monthlyReports = ViolationReport::whereMonth('created_at', now()->month)->count();
        $resolutionRate = $totalReports > 0 ? round(($resolvedReports / $totalReports) * 100, 1) : 0;

        return view('response-tracking.index', compact('reports', 'barangays', 'totalReports', 'pendingReports', 'inProgressReports', 'resolvedReports', 'avgResponseTime', 'monthlyReports', 'resolutionRate'));
    }

    /**
     * Calculate average response days
     */
    private function calculateAverageResponseDays()
    {
        $resolved = ViolationReport::whereIn('status', ['Resolved', 'Closed'])
            ->whereNotNull('date_updated')
            ->get();

        if ($resolved->isEmpty()) {
            return 0;
        }

        $totalDays = $resolved->sum(function($report) {
            return $report->date_submitted->diffInDays($report->date_updated);
        });

        return round($totalDays / $resolved->count(), 1);
    }
}
