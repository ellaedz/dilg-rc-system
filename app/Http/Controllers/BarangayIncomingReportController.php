<?php

namespace App\Http\Controllers;

use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use Illuminate\Http\Request;

class BarangayIncomingReportController extends Controller
{
    /**
     * Display incoming reports for specific barangay (Submitted or For Verification)
     */
    public function index($barangay, Request $request)
    {
        // Verify barangay exists
        $barangayDetails = BarangayAssignmentService::getBarangayByName($barangay);
        if (!$barangayDetails) {
            abort(404, 'Barangay not found');
        }

        // Query only reports for this barangay
        $query = ViolationReport::forEffectiveBarangay($barangay)
            ->whereIn('status', ['Submitted', 'For Verification']);

        // Search
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('report_id', 'like', "%{$search}%")
                  ->orWhere('submitted_by', 'like', "%{$search}%")
                  ->orWhere('selected_violation_type', 'like', "%{$search}%");
            });
        }

        // Filter by violation type
        if ($request->has('violation_type') && $request->violation_type != '') {
            $query->where('selected_violation_type', $request->violation_type);
        }

        $reports = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('barangay.incoming-reports', compact('reports', 'barangay'));
    }

    /**
     * Verify report (change status to Verified, verification_status to Valid Violation)
     */
    public function verify(Request $request, $barangay, ViolationReport $report)
    {
        // Ensure report belongs to this barangay
        if (strcasecmp((string) $report->effective_barangay, $barangay) !== 0) {
            abort(403, 'Unauthorized action');
        }

        $report->update([
            'status' => 'Verified',
            'verification_status' => 'Valid Violation',
            'remarks' => $request->remarks,
            'date_updated' => now()
        ]);

        return redirect()->route('barangay.incoming-reports', $barangay)
            ->with('success', 'Report verified successfully!');
    }

    /**
     * Reject report
     */
    public function reject(Request $request, $barangay, ViolationReport $report)
    {
        // Ensure report belongs to this barangay
        if (strcasecmp((string) $report->effective_barangay, $barangay) !== 0) {
            abort(403, 'Unauthorized action');
        }

        $report->update([
            'status' => 'Rejected',
            'verification_status' => 'Invalid Report',
            'remarks' => $request->remarks ?? 'Report rejected by barangay staff',
            'date_updated' => now()
        ]);

        return redirect()->route('barangay.incoming-reports', $barangay)
            ->with('success', 'Report rejected.');
    }

    /**
     * AJAX: Get real-time incoming reports updates
     */
    public function getUpdates($barangay)
    {
        $reports = ViolationReport::forEffectiveBarangay($barangay)
            ->whereIn('status', ['Submitted', 'For Verification'])
            ->select('id', 'report_id', 'status', 'selected_violation_type', 'submitted_by', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        return response()->json([
            'reports' => $reports,
            'count' => $reports->count(),
            'timestamp' => now()->toISOString()
        ]);
    }
}
