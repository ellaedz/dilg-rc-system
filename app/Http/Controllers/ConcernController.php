<?php

namespace App\Http\Controllers;

use App\Models\Record;
use App\Services\BarangayAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConcernController extends Controller
{
    /**
     * Display all concerns (submitted from mobile or manual encoding)
     */
    public function index(Request $request)
    {
        $query = Record::query();

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

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Filter by concern type
        if ($request->has('concern_type') && $request->concern_type != '') {
            $query->where('concern_type', $request->concern_type);
        }

        // Filter by barangay
        if ($request->has('barangay') && $request->barangay != '') {
            $query->where('detected_barangay', $request->barangay);
        }

        // Filter by urgency level
        if ($request->has('urgency') && $request->urgency != '') {
            $query->where('urgency_level', $request->urgency);
        }

        $concerns = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get all barangays for filter dropdown
        $barangays = BarangayAssignmentService::getAllBarangays();

        return view('concerns.index', compact('concerns', 'barangays'));
    }

    /**
     * Show the form for creating a new concern (manual encoding by staff)
     */
    public function create()
    {
        $barangays = BarangayAssignmentService::getAllBarangays();
        return view('concerns.create', compact('barangays'));
    }

    /**
     * Store a newly created concern (manual encoding by staff)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string',
            'concern_type' => 'required|in:Complaint,Request,Referral,Inquiry,Infrastructure Concern,Governance Concern,Public Service Concern,Environmental Concern,Disaster / Risk Concern,Road Clearing / Obstruction,Other',
            'description' => 'required|string',
            'photo_evidence' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'urgency_level' => 'required|in:Low,Medium,High,Critical',
            'assigned_personnel' => 'nullable|string|max:255',
            'remarks' => 'nullable|string'
        ]);

        // Handle photo upload
        if ($request->hasFile('photo_evidence')) {
            $path = $request->file('photo_evidence')->store('concern_photos', 'public');
            $validated['photo_evidence'] = $path;
        }

        // Auto-detect barangay from GPS coordinates
        if (!empty($validated['latitude']) && !empty($validated['longitude'])) {
            $barangayData = BarangayAssignmentService::detectBarangay(
                $validated['latitude'],
                $validated['longitude']
            );
            
            $validated['detected_barangay'] = $barangayData['detected_barangay'];
            $validated['assigned_barangay_office'] = $barangayData['assigned_barangay_office'];
            $validated['location_name'] = $barangayData['location_name'];
            $validated['gps_accuracy'] = 10.0; // Default accuracy for manual entry
        } else {
            $validated['detected_barangay'] = 'Location Not Available';
            $validated['assigned_barangay_office'] = 'Unassigned';
            $validated['location_name'] = 'GPS coordinates not provided';
        }

        $validated['record_id'] = Record::generateRecordId();
        $validated['date_submitted'] = now();
        $validated['status'] = 'Submitted';
        $validated['assigned_office'] = $validated['assigned_barangay_office'];

        Record::create($validated);

        return redirect()->route('concerns.index')
            ->with('success', 'Concern submitted successfully! ID: ' . $validated['record_id']);
    }

    /**
     * Display the specified concern
     */
    public function show(Record $concern)
    {
        return view('concerns.show', compact('concern'));
    }

    /**
     * Show the form for editing the specified concern
     */
    public function edit(Record $concern)
    {
        $barangays = BarangayAssignmentService::getAllBarangays();
        return view('concerns.edit', compact('concern', 'barangays'));
    }

    /**
     * Update the specified concern
     * Note: GPS coordinates and barangay assignment are NOT editable
     */
    public function update(Request $request, Record $concern)
    {
        $validated = $request->validate([
            'status' => 'required|in:Submitted,Under Review,Assigned,In Progress,Referred,Resolved,Closed,Rejected',
            'urgency_level' => 'required|in:Low,Medium,High,Critical',
            'assigned_personnel' => 'nullable|string|max:255',
            'remarks' => 'nullable|string'
        ]);

        // Only allow updating these fields
        $concern->update($validated);

        return redirect()->route('concerns.show', $concern)
            ->with('success', 'Concern updated successfully!');
    }

    /**
     * Remove the specified concern
     */
    public function destroy(Record $concern)
    {
        $concern->delete();

        return redirect()->route('concerns.index')
            ->with('success', 'Concern deleted successfully!');
    }
}
