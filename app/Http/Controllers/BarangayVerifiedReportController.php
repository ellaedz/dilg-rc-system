<?php

namespace App\Http\Controllers;

use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use Illuminate\Http\Request;

class BarangayVerifiedReportController extends Controller
{
    /**
     * Display verified reports for specific barangay
     */
    public function index($barangay, Request $request)
    {
        // Verify barangay exists
        $barangayDetails = BarangayAssignmentService::getBarangayByName($barangay);
        if (!$barangayDetails) {
            abort(404, 'Barangay not found');
        }

        // Query only verified reports for this barangay
        $query = ViolationReport::forEffectiveBarangay($barangay)
            ->whereIn('status', ['Verified', 'Assigned', 'In Progress', 'Action Taken']);

        // Search
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('report_id', 'like', "%{$search}%")
                  ->orWhere('submitted_by', 'like', "%{$search}%")
                  ->orWhere('assigned_personnel', 'like', "%{$search}%");
            });
        }

        // Filter by violation type
        if ($request->has('violation_type') && $request->violation_type != '') {
            $query->where('selected_violation_type', $request->violation_type);
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $reports = $query->orderBy('date_updated', 'desc')->paginate(15);

        return view('barangay.verified-reports', compact('reports', 'barangay'));
    }

    /**
     * Assign personnel to report
     */
    public function assign(Request $request, $barangay, ViolationReport $report)
    {
        // Ensure report belongs to this barangay
        if (strcasecmp((string) $report->effective_barangay, $barangay) !== 0) {
            abort(403, 'Unauthorized action');
        }

        $request->validate([
            'assigned_personnel' => 'required|string|max:255',
        ]);

        $report->update([
            'status' => 'Assigned',
            'assigned_personnel' => $request->assigned_personnel,
            'response_started_at' => now(),
            'remarks' => $request->remarks,
            'date_updated' => now()
        ]);

        return redirect()->route('barangay.verified-reports', $barangay)
            ->with('success', 'Report assigned to ' . $request->assigned_personnel);
    }
}
