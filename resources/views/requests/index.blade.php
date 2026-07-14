@extends('layouts.app')

@section('title', 'Requests - DILG-RC System')

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
        grid-template-columns: 2fr 1fr auto;
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

    .data-table tr:hover {
        background: #fefce8;
    }

    .request-id {
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
    .badge-in-progress { background: #dbeafe; color: #1e40af; }
    .badge-resolved { background: #d1fae5; color: #065f46; }

    .badge-high { background: #fee2e2; color: #991b1b; }
    .badge-medium { background: #fed7aa; color: #9a3412; }
    .badge-low { background: #e0e7ff; color: #3730a3; }

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
</style>

<div class="page-header">
    <h1 class="page-title">📋 Requests Management</h1>
    <a href="#" class="btn-primary">+ New Request</a>
</div>

<!-- Search and Filters -->
<div class="search-filter-section">
    <form method="GET" action="{{ route('requests.index') }}">
        <div class="search-filter-grid">
            <div class="form-group">
                <label class="form-label">Search</label>
                <input 
                    type="text" 
                    name="search" 
                    class="form-input" 
                    placeholder="Search by ID, name, or subject..."
                    value="{{ request('search') }}"
                >
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="In Progress" {{ request('status') == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="Resolved" {{ request('status') == 'Resolved' ? 'selected' : '' }}>Resolved</option>
                </select>
            </div>
            <button type="submit" class="btn-filter">Filter</button>
        </div>
    </form>
</div>

<!-- Requests Table -->
<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Request ID</th>
                <th>Name</th>
                <th>Barangay</th>
                <th>Request Type</th>
                <th>Subject</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Date Filed</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requests as $request)
            <tr>
                <td><span class="request-id">{{ $request['request_id'] }}</span></td>
                <td>{{ $request['full_name'] }}</td>
                <td>{{ $request['barangay'] }}</td>
                <td>{{ $request['request_type'] }}</td>
                <td>{{ $request['subject'] }}</td>
                <td>
                    <span class="badge badge-{{ strtolower($request['priority']) }}">
                        {{ $request['priority'] }}
                    </span>
                </td>
                <td>
                    <span class="badge badge-{{ strtolower(str_replace(' ', '-', $request['status'])) }}">
                        {{ $request['status'] }}
                    </span>
                </td>
                <td>{{ $request['date_filed'] }}</td>
                <td>
                    <div class="btn-actions">
                        <a href="{{ route('requests.show', $request['id']) }}" class="btn-sm btn-view">View</a>
                        <a href="{{ route('requests.edit', $request['id']) }}" class="btn-sm btn-edit">Edit</a>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="margin-top: 1.5rem; padding: 1rem; background: #fef3c7; border-radius: 0.5rem; border-left: 4px solid #F4C542; color: #78350f;">
    <strong>📝 Note:</strong> This is dummy data for Phase 2 demonstration. Full database integration will be added in future phases.
</div>
@endsection
