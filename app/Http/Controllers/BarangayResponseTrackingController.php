<?php

namespace App\Http\Controllers;

use App\Models\ViolationReport;
use App\Models\ReportTimeline;
use App\Services\BarangayAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarangayResponseTrackingController extends Controller
{
    /**
     * Display response tracking for specific barangay
     */
    public function index($barangay, Request $request)
    {
        // Verify barangay exists
        $barangayDetails = BarangayAssignmentService::getBarangayByName($barangay);
        if (!$barangayDetails) {
            abort(404, 'Barangay not found');
        }

        // Query only reports for this barangay
        $query = ViolationReport::forEffectiveBarangay($barangay);

        // Search
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('report_id', 'like', "%{$search}%")
                  ->orWhere('submitted_by', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $reports = $query->orderBy('date_updated', 'desc')
                        ->orderBy('created_at', 'desc')
                        ->paginate(15);

        // Calculate response time statistics for this barangay
        $totalReports = ViolationReport::forEffectiveBarangay($barangay)->count();
        $pendingReports = ViolationReport::forEffectiveBarangay($barangay)
            ->whereIn('status', ['Submitted', 'For Verification', 'Verified'])->count();
        $inProgressReports = ViolationReport::forEffectiveBarangay($barangay)
            ->whereIn('status', ['Assigned', 'In Progress'])->count();
        $resolvedReports = ViolationReport::forEffectiveBarangay($barangay)
            ->where('status', 'Resolved')->count();
        
        $avgResponseTime = $this->calculateAverageResponseTime($barangay);
        $monthlyReports = ViolationReport::forEffectiveBarangay($barangay)
            ->whereMonth('created_at', now()->month)->count();
        $resolutionRate = $totalReports > 0 ? round(($resolvedReports / $totalReports) * 100, 1) : 0;

        return view('barangay.response-tracking', compact(
            'reports', 
            'barangay', 
            'totalReports', 
            'pendingReports', 
            'inProgressReports', 
            'resolvedReports', 
            'avgResponseTime', 
            'monthlyReports', 
            'resolutionRate'
        ));
    }

    /**
     * Calculate average response time for barangay
     */
    private function calculateAverageResponseTime($barangay)
    {
        $resolved = ViolationReport::forEffectiveBarangay($barangay)
            ->whereIn('status', ['Resolved', 'Closed'])
            ->whereNotNull('response_time_hours')
            ->get();

        if ($resolved->isEmpty()) {
            return 0;
        }

        return round($resolved->avg('response_time_hours'), 1);
    }

    /**
     * Update report (status, personnel, action taken, remarks)
     * Supports both normal form submission and AJAX requests
     */
    public function update(Request $request, $barangay, ViolationReport $report)
    {
        // Ensure report belongs to this barangay (case-insensitive)
        if (strcasecmp((string) $report->effective_barangay, $barangay) !== 0) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized action'
                ], 403);
            }
            abort(403, 'Unauthorized action');
        }

        $validated = $request->validate([
            'status' => 'required|in:Submitted,For Verification,Verified,Assigned,In Progress,Action Taken,Resolved,Rejected,Closed',
            'assigned_personnel' => 'nullable|string|max:255',
            'action_taken' => 'nullable|string',
            'remarks' => 'nullable|string'
        ]);

        // Store old status for timeline
        $oldStatus = $report->status;

        $validated['date_updated'] = now();
        
        // If status changed to Resolved, record resolved_at and calculate response time
        if ($request->status === 'Resolved' && $report->status !== 'Resolved') {
            $validated['resolved_at'] = now();
            
            // Calculate response time if response_started_at exists
            if ($report->response_started_at) {
                $responseTimeHours = $report->response_started_at->diffInHours(now());
                $validated['response_time_hours'] = $responseTimeHours;
            }
        }
        
        // If status changed to Assigned and response hasn't started, mark response start
        if ($request->status === 'Assigned' && !$report->response_started_at) {
            $validated['response_started_at'] = now();
        }

        // If status changed to Verified, update verification_status
        if ($request->status === 'Verified' && $report->status !== 'Verified') {
            $validated['verification_status'] = 'Valid Violation';
        }

        // If status changed to Rejected, update verification_status
        if ($request->status === 'Rejected' && $report->status !== 'Rejected') {
            $validated['verification_status'] = 'Invalid Report';
        }
        
        $report->update($validated);

        // Create timeline entry for this status update
        ReportTimeline::create([
            'report_id' => $report->id,
            'status' => $request->status,
            'old_status' => $oldStatus !== $request->status ? $oldStatus : null,
            'remarks' => $request->remarks,
            'action_taken' => $request->action_taken,
            'assigned_personnel' => $request->assigned_personnel,
            'updated_by' => Auth::id(),
        ]);

        // AJAX Response
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully!',
                'data' => [
                    'status' => $report->status,
                    'verification_status' => $report->verification_status,
                    'latest_action' => $request->action_taken ?? $request->remarks,
                    'assigned_personnel' => $request->assigned_personnel,
                    'date_updated' => $report->date_updated->format('M d, Y h:i A')
                ]
            ]);
        }

        // Normal Form Response
        return redirect()->route('violation-reports.show', $report)
            ->with('success', 'Report updated successfully!');
    }
}
