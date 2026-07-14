<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RequestController extends Controller
{
    /**
     * Display a listing of requests.
     */
    public function index(Request $request)
    {
        // Dummy data for Phase 2 - will be replaced with database in future phases
        $requests = collect([
            [
                'id' => 1,
                'request_id' => 'REQ-2026-001',
                'full_name' => 'Maria Santos',
                'contact_number' => '09171234567',
                'email' => 'maria.santos@email.com',
                'barangay' => 'Poblacion II',
                'request_type' => 'Document Request',
                'subject' => 'Barangay Clearance',
                'description' => 'Requesting barangay clearance for employment purposes.',
                'status' => 'Pending',
                'priority' => 'Medium',
                'assigned_personnel' => null,
                'date_filed' => '2026-06-10',
                'last_updated' => '2026-06-10',
            ],
            [
                'id' => 2,
                'request_id' => 'REQ-2026-002',
                'full_name' => 'Juan Dela Cruz',
                'contact_number' => '09189876543',
                'email' => 'juan.delacruz@email.com',
                'barangay' => 'Oogong',
                'request_type' => 'Service Request',
                'subject' => 'Street Light Repair',
                'description' => 'Request for repair of broken street lights along Main Street.',
                'status' => 'In Progress',
                'priority' => 'High',
                'assigned_personnel' => 'Engr. Pedro Reyes',
                'date_filed' => '2026-06-09',
                'last_updated' => '2026-06-10',
            ],
            [
                'id' => 3,
                'request_id' => 'REQ-2026-003',
                'full_name' => 'Rosa Garcia',
                'contact_number' => '09195554321',
                'email' => 'rosa.garcia@email.com',
                'barangay' => 'Pagsawitan',
                'request_type' => 'Information Request',
                'subject' => 'Building Permit Requirements',
                'description' => 'Inquiry about requirements for building permit application.',
                'status' => 'Resolved',
                'priority' => 'Low',
                'assigned_personnel' => 'Ms. Ana Lopez',
                'date_filed' => '2026-06-08',
                'last_updated' => '2026-06-09',
            ],
        ]);

        // Apply filters
        if ($request->has('status') && $request->status != '') {
            $requests = $requests->where('status', $request->status);
        }

        if ($request->has('search') && $request->search != '') {
            $search = strtolower($request->search);
            $requests = $requests->filter(function($item) use ($search) {
                return str_contains(strtolower($item['request_id']), $search) ||
                       str_contains(strtolower($item['full_name']), $search) ||
                       str_contains(strtolower($item['subject']), $search);
            });
        }

        return view('requests.index', compact('requests'));
    }

    /**
     * Show the form for creating a new request.
     */
    public function create()
    {
        return view('requests.create');
    }

    /**
     * Store a newly created request in storage.
     */
    public function store(Request $request)
    {
        // Validation only for Phase 2 - actual storage will be implemented later
        $request->validate([
            'full_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'barangay' => 'required|string|max:255',
            'request_type' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:High,Medium,Low',
        ]);

        return redirect()->route('requests.index')
            ->with('success', 'Request submitted successfully! (Demo Mode)');
    }

    /**
     * Display the specified request.
     */
    public function show($id)
    {
        // Dummy data for individual request
        $request = [
            'id' => $id,
            'request_id' => 'REQ-2026-00' . $id,
            'full_name' => 'Maria Santos',
            'contact_number' => '09171234567',
            'email' => 'maria.santos@email.com',
            'barangay' => 'Poblacion II',
            'request_type' => 'Document Request',
            'subject' => 'Barangay Clearance',
            'description' => 'Requesting barangay clearance for employment purposes.',
            'status' => 'Pending',
            'priority' => 'Medium',
            'assigned_personnel' => 'Ms. Ana Lopez',
            'date_filed' => '2026-06-10',
            'last_updated' => '2026-06-10',
            'resolution_notes' => null,
        ];

        return view('requests.show', compact('request'));
    }

    /**
     * Show the form for editing the specified request.
     */
    public function edit($id)
    {
        // Dummy data for edit form
        $request = [
            'id' => $id,
            'request_id' => 'REQ-2026-00' . $id,
            'full_name' => 'Maria Santos',
            'contact_number' => '09171234567',
            'email' => 'maria.santos@email.com',
            'barangay' => 'Poblacion II',
            'request_type' => 'Document Request',
            'subject' => 'Barangay Clearance',
            'description' => 'Requesting barangay clearance for employment purposes.',
            'status' => 'Pending',
            'priority' => 'Medium',
            'assigned_personnel' => null,
        ];

        return view('requests.edit', compact('request'));
    }

    /**
     * Update the specified request in storage.
     */
    public function update(Request $request, $id)
    {
        // Validation only for Phase 2
        $request->validate([
            'status' => 'required|in:Pending,Under Review,In Progress,Referred,Resolved,Closed',
            'assigned_personnel' => 'nullable|string|max:255',
            'resolution_notes' => 'nullable|string',
        ]);

        return redirect()->route('requests.show', $id)
            ->with('success', 'Request updated successfully! (Demo Mode)');
    }
}
