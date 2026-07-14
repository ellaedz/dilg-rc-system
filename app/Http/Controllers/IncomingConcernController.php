<?php

namespace App\Http\Controllers;

use App\Models\Record;
use App\Services\BarangayAssignmentService;
use Illuminate\Http\Request;

class IncomingConcernController extends Controller
{
    /**
     * Display concerns that are newly submitted (from mobile app)
     * Status: Submitted or Under Review
     */
    public function index(Request $request)
    {
        $query = Record::query()
            ->whereIn('status', ['Submitted', 'Under Review']);

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('record_id', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('concern_type', 'like', "%{$search}%")
                  ->orWhere('detected_barangay', 'like', "%{$search}%");
            });
        }

        // Filter by concern type
        if ($request->has('concern_type') && $request->concern_type != '') {
            $query->where('concern_type', $request->concern_type);
        }

        // Filter by barangay
        if ($request->has('barangay') && $request->barangay != '') {
            $query->where('detected_barangay', $request->barangay);
        }

        // Filter by urgency
        if ($request->has('urgency') && $request->urgency != '') {
            $query->where('urgency_level', $request->urgency);
        }

        $incomingConcerns = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get all barangays for filter
        $barangays = BarangayAssignmentService::getAllBarangays();

        return view('incoming-concerns.index', compact('incomingConcerns', 'barangays'));
    }

    /**
     * Quick assign concern to personnel
     */
    public function assign(Request $request, Record $concern)
    {
        $request->validate([
            'assigned_personnel' => 'required|string|max:255',
        ]);

        $concern->update([
            'status' => 'Assigned',
            'assigned_personnel' => $request->assigned_personnel,
            'assigned_office' => $concern->assigned_barangay_office,
        ]);

        return redirect()->route('incoming-concerns.index')
            ->with('success', 'Concern assigned to ' . $request->assigned_personnel);
    }
}
