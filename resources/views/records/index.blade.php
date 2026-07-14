@extends('layouts.app')

@section('title', 'Records Management - DILG-RC')

@section('content')
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .page-title {
        font-size: 2rem;
        font-weight: bold;
        color: #333;
    }

    .btn-primary {
        background: linear-gradient(135deg, #F4C542 0%, #D4A017 100%);
        color: #333;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(212, 160, 23, 0.4);
    }

    .search-filter-section {
        background: white;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .search-filter-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto;
        gap: 1rem;
        align-items: end;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
    }

    .form-input, .form-select {
        padding: 0.75rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 0.9375rem;
        transition: all 0.3s;
    }

    .form-input:focus, .form-select:focus {
        outline: none;
        border-color: #F4C542;
        box-shadow: 0 0 0 3px rgba(244, 197, 66, 0.1);
    }

    .btn-filter {
        background: #333;
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-filter:hover {
        background: #555;
    }

    .table-container {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        overflow: hidden;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table th {
        background: #f9fafb;
        padding: 1rem 1.5rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #F4C542;
    }

    .data-table td {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .data-table tr:last-child td {
        border-bottom: none;
    }

    .data-table tbody tr:hover {
        background: #fefce8;
    }

    .record-id {
        font-weight: 600;
        color: #D4A017;
    }

    .badge {
        display: inline-block;
        padding: 0.375rem 0.875rem;
        border-radius: 1rem;
        font-size: 0.8125rem;
        font-weight: 600;
    }

    .badge-pending { background: #fef3c7; color: #92400e; }
    .badge-under-review { background: #dbeafe; color: #1e40af; }
    .badge-in-progress { background: #bfdbfe; color: #1e3a8a; }
    .badge-resolved { background: #d1fae5; color: #065f46; }
    .badge-referred { background: #e0e7ff; color: #3730a3; }
    .badge-closed { background: #e5e7eb; color: #374151; }

    .btn-actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn-sm {
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.8125rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        border: none;
    }

    .btn-view { background: #dbeafe; color: #1e40af; }
    .btn-view:hover { background: #bfdbfe; }
    .btn-edit { background: #fef3c7; color: #92400e; }
    .btn-edit:hover { background: #fde68a; }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        padding: 1.5rem;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #6b7280;
    }
</style>

<div class="page-header">
    <h1 class="page-title">📁 Records Management</h1>
    <a href="{{ route('records.create') }}" class="btn-primary">+ New Record</a>
</div>

<!-- Search and Filters -->
<div class="search-filter-section">
    <form method="GET" action="{{ route('records.index') }}">
        <div class="search-filter-grid">
            <div class="form-group">
                <label class="form-label">Search</label>
                <input 
                    type="text" 
                    name="search" 
                    class="form-input" 
                    placeholder="Search by ID, name, or concern..."
                    value="{{ request('search') }}"
                >
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Under Review" {{ request('status') == 'Under Review' ? 'selected' : '' }}>Under Review</option>
                    <option value="In Progress" {{ request('status') == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="Referred" {{ request('status') == 'Referred' ? 'selected' : '' }}>Referred</option>
                    <option value="Resolved" {{ request('status') == 'Resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="Closed" {{ request('status') == 'Closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Concern Type</label>
                <select name="concern_type" class="form-select">
                    <option value="">All Types</option>
                    <option value="Complaint" {{ request('concern_type') == 'Complaint' ? 'selected' : '' }}>Complaint</option>
                    <option value="Request" {{ request('concern_type') == 'Request' ? 'selected' : '' }}>Request</option>
                    <option value="Referral" {{ request('concern_type') == 'Referral' ? 'selected' : '' }}>Referral</option>
                    <option value="Inquiry" {{ request('concern_type') == 'Inquiry' ? 'selected' : '' }}>Inquiry</option>
                    <option value="Infrastructure Concern" {{ request('concern_type') == 'Infrastructure Concern' ? 'selected' : '' }}>Infrastructure</option>
                    <option value="Governance Concern" {{ request('concern_type') == 'Governance Concern' ? 'selected' : '' }}>Governance</option>
                    <option value="Public Service Concern" {{ request('concern_type') == 'Public Service Concern' ? 'selected' : '' }}>Public Service</option>
                    <option value="Other" {{ request('concern_type') == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <button type="submit" class="btn-filter">Filter</button>
        </div>
    </form>
</div>

<!-- Records Table -->
<div class="table-container">
    @if($records->count() > 0)
    <table class="data-table">
        <thead>
            <tr>
                <th>Record ID</th>
                <th>Name</th>
                <th>Contact</th>
                <th>Concern Type</th>
                <th>Status</th>
                <th>Date Submitted</th>
                <th>Assigned To</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $record)
            <tr>
                <td><span class="record-id">{{ $record->record_id }}</span></td>
                <td>{{ $record->full_name }}</td>
                <td>{{ $record->contact_number }}</td>
                <td>{{ $record->concern_type }}</td>
                <td>
                    <span class="badge badge-{{ strtolower(str_replace(' ', '-', $record->status)) }}">
                        {{ $record->status }}
                    </span>
                </td>
                <td>{{ $record->date_submitted ? $record->date_submitted->format('M d, Y') : 'N/A' }}</td>
                <td>{{ $record->assigned_personnel ?? 'Unassigned' }}</td>
                <td>
                    <div class="btn-actions">
                        <a href="{{ route('records.show', $record) }}" class="btn-sm btn-view">View</a>
                        <a href="{{ route('records.edit', $record) }}" class="btn-sm btn-edit">Edit</a>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="pagination">
        {{ $records->links() }}
    </div>
    @else
    <div class="empty-state">
        <p>No records found. Start by creating a new record.</p>
    </div>
    @endif
</div>
@endsection
