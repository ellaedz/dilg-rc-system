<?php

namespace App\Http\Controllers;

use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use Illuminate\Http\Request;

class IncomingReportController extends Controller
{
    /**
     * Display incoming reports (Submitted or For Verification)
     */
    public function index(Request $request)
    {
        $query = ViolationReport::query()
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

        // Filter by barangay
        if ($request->has('barangay') && $request->barangay != '') {
            $query->where('detected_barangay', $request->barangay);
        }

        // Filter by GPS status
        if ($request->has('gps_status')) {
            if ($request->gps_status == 'with_gps') {
                $query->whereNotNull('latitude')->whereNotNull('longitude');
            } elseif ($request->gps_status == 'without_gps') {
                $query->whereNull('latitude')->orWhereNull('longitude');
            }
        }

        $reports = $query->orderBy('created_at', 'desc')->paginate(15);
        $barangays = BarangayAssignmentService::getAllBarangays();

        return view('incoming-reports.index', compact('reports', 'barangays'));
    }

    /**
     * Verify report (change status to Verified)
     */
    public function verify(Request $request, ViolationReport $report)
    {
        $request->validate([
            'remarks' => 'nullable|string'
        ]);

        $report->update([
            'status' => 'Verified',
            'remarks' => $request->remarks,
            'date_updated' => now()
        ]);

        return redirect()->route('incoming-reports.index')
            ->with('success', 'Report verified successfully!');
    }

    /**
     * Reject report
     */
    public function reject(Request $request, ViolationReport $report)
    {
        $request->validate([
            'remarks' => 'required|string'
        ]);

        $report->update([
            'status' => 'Rejected',
            'remarks' => $request->remarks,
            'date_updated' => now()
        ]);

        return redirect()->route('incoming-reports.index')
            ->with('success', 'Report rejected.');
    }
}
