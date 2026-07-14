<?php

namespace App\Http\Controllers;

use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use Illuminate\Http\Request;

class VerifiedReportController extends Controller
{
    /**
     * Display verified reports (Verified, Assigned, In Progress)
     */
    public function index(Request $request)
    {
        $query = ViolationReport::query()
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

        // Filter by barangay
        if ($request->has('barangay') && $request->barangay != '') {
            $query->where('detected_barangay', $request->barangay);
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Filter by assigned personnel
        if ($request->has('personnel') && $request->personnel != '') {
            $query->where('assigned_personnel', 'like', "%{$request->personnel}%");
        }

        $reports = $query->orderBy('date_updated', 'desc')->paginate(15);
        $barangays = BarangayAssignmentService::getAllBarangays();

        return view('verified-reports.index', compact('reports', 'barangays'));
    }

    /**
     * Assign personnel to report
     */
    public function assign(Request $request, ViolationReport $report)
    {
        $request->validate([
            'assigned_personnel' => 'required|string|max:255',
            'remarks' => 'nullable|string'
        ]);

        $report->update([
            'status' => 'Assigned',
            'assigned_personnel' => $request->assigned_personnel,
            'remarks' => $request->remarks,
            'date_updated' => now()
        ]);

        return redirect()->route('verified-reports.index')
            ->with('success', 'Report assigned to ' . $request->assigned_personnel);
    }
}
