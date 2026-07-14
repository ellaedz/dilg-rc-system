<?php

namespace App\Http\Controllers;

use App\Models\Record;
use Illuminate\Http\Request;

class RecordController extends Controller
{
    public function index(Request $request)
    {
        $query = Record::query();

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('record_id', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('concern_type', 'like', "%{$search}%");
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

        $records = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('records.index', compact('records'));
    }

    public function create()
    {
        return view('records.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string',
            'concern_type' => 'required|in:Complaint,Request,Referral,Inquiry,Infrastructure Concern,Governance Concern,Public Service Concern,Other',
            'description' => 'required|string',
            'assigned_office' => 'nullable|string|max:255',
            'assigned_personnel' => 'nullable|string|max:255',
            'remarks' => 'nullable|string'
        ]);

        $validated['record_id'] = Record::generateRecordId();
        $validated['date_submitted'] = now();
        $validated['status'] = 'Pending';

        Record::create($validated);

        return redirect()->route('records.index')
            ->with('success', 'Record created successfully! ID: ' . $validated['record_id']);
    }

    public function show(Record $record)
    {
        return view('records.show', compact('record'));
    }

    public function edit(Record $record)
    {
        return view('records.edit', compact('record'));
    }

    public function update(Request $request, Record $record)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string',
            'concern_type' => 'required|in:Complaint,Request,Referral,Inquiry,Infrastructure Concern,Governance Concern,Public Service Concern,Other',
            'description' => 'required|string',
            'status' => 'required|in:Pending,Under Review,In Progress,Referred,Resolved,Closed',
            'assigned_office' => 'nullable|string|max:255',
            'assigned_personnel' => 'nullable|string|max:255',
            'remarks' => 'nullable|string'
        ]);

        $record->update($validated);

        return redirect()->route('records.show', $record)
            ->with('success', 'Record updated successfully!');
    }

    public function destroy(Record $record)
    {
        $record->delete();

        return redirect()->route('records.index')
            ->with('success', 'Record deleted successfully!');
    }
}

