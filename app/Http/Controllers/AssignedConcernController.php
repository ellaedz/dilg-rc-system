<?php

namespace App\Http\Controllers;

use App\Models\Record;
use App\Services\BarangayAssignmentService;
use Illuminate\Http\Request;

class AssignedConcernController extends Controller
{
    /**
     * Display concerns that have been assigned to staff
     * Status: Assigned, In Progress
     */
    public function index(Request $request)
    {
        $query = Record::query()
            ->whereIn('status', ['Assigned', 'In Progress'])
            ->whereNotNull('assigned_personnel');

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('record_id', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('assigned_personnel', 'like', "%{$search}%")
                  ->orWhere('detected_barangay', 'like', "%{$search}%");
            });
        }

        // Filter by assigned personnel
        if ($request->has('assigned_personnel') && $request->assigned_personnel != '') {
            $query->where('assigned_personnel', 'like', "%{$request->assigned_personnel}%");
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Filter by barangay
        if ($request->has('barangay') && $request->barangay != '') {
            $query->where('detected_barangay', $request->barangay);
        }

        // Filter by urgency
        if ($request->has('urgency') && $request->urgency != '') {
            $query->where('urgency_level', $request->urgency);
        }

        $assignedConcerns = $query->orderBy('updated_at', 'desc')->paginate(15);

        // Get all barangays for filter
        $barangays = BarangayAssignmentService::getAllBarangays();

        return view('assigned-concerns.index', compact('assignedConcerns', 'barangays'));
    }
}
