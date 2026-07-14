<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $query = Complaint::query();

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('complaint_id', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->has('priority') && $request->priority != '') {
            $query->where('priority', $request->priority);
        }

        $complaints = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('complaints.index', compact('complaints'));
    }

    public function create()
    {
        return view('complaints.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'address' => 'required|string',
            'barangay' => 'required|string|max:255',
            'municipality' => 'required|string|max:255',
            'concern_type' => 'required|in:Complaint,Request,Referral,Inquiry,Report',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:High,Medium,Low',
            'assigned_office' => 'nullable|string|max:255',
            'assigned_personnel' => 'nullable|string|max:255',
            'remarks' => 'nullable|string'
        ]);

        $validated['complaint_id'] = Complaint::generateComplaintId();
        $validated['date_filed'] = now();
        $validated['status'] = 'Pending';
        $validated['province'] = 'Laguna';

        Complaint::create($validated);

        return redirect()->route('complaints.index')
            ->with('success', 'Complaint filed successfully! ID: ' . $validated['complaint_id']);
    }

    public function show(Complaint $complaint)
    {
        return view('complaints.show', compact('complaint'));
    }

    public function edit(Complaint $complaint)
    {
        return view('complaints.edit', compact('complaint'));
    }

    public function update(Request $request, Complaint $complaint)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'address' => 'required|string',
            'barangay' => 'required|string|max:255',
            'municipality' => 'required|string|max:255',
            'concern_type' => 'required|in:Complaint,Request,Referral,Inquiry,Report',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:High,Medium,Low',
            'status' => 'required|in:Pending,In Progress,Resolved,Referred,Closed',
            'assigned_office' => 'nullable|string|max:255',
            'assigned_personnel' => 'nullable|string|max:255',
            'remarks' => 'nullable|string'
        ]);

        $complaint->update($validated);

        return redirect()->route('complaints.show', $complaint)
            ->with('success', 'Complaint updated successfully!');
    }

    public function destroy(Complaint $complaint)
    {
        $complaint->delete();

        return redirect()->route('complaints.index')
            ->with('success', 'Complaint deleted successfully!');
    }
}

