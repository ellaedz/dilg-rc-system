@extends('layouts.app')

@section('title', 'Complaints & Requests - DILG-RC System')

@section('content')
<style>
    .page-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .filter-group {
        display: flex;
        gap: 0.75rem;
    }

    .filter-select, .search-input {
        padding: 0.75rem 1rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        background: white;
    }

    .search-input {
        width: 250px;
    }

    .filter-select:focus, .search-input:focus {
        outline: none;
        border-color: #3b82f6;
    }

    .table-container {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        background: #f9fafb;
    }

    th {
        padding: 1rem 1.5rem;
        text-align: left;
        font-size: 0.75rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    td {
        padding: 1rem 1.5rem;
        border-top: 1px solid #e5e7eb;
        color: #374151;
    }

    tbody tr:hover {
        background: #f9fafb;
    }

    .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-resolved {
        background: #d1fae5;
        color: #065f46;
    }

    .badge-in-progress {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-referred {
        background: #e0e7ff;
        color: #3730a3;
    }

    .badge-closed {
        background: #e5e7eb;
        color: #374151;
    }

    .priority-high {
        color: #dc2626;
        font-weight: 600;
    }

    .priority-medium {
        color: #f59e0b;
        font-weight: 600;
    }

    .priority-low {
        color: #10b981;
        font-weight: 600;
    }

    .btn-action {
        padding: 0.375rem 0.75rem;
        margin-right: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        background: white;
        color: #374151;
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-block;
    }

    .btn-action:hover {
        background: #f9fafb;
        border-color: #3b82f6;
        color: #3b82f6;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        padding: 1.5rem;
        background: white;
    }

    .pagination a, .pagination span {
        padding: 0.5rem 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        color: #374151;
        text-decoration: none;
        font-size: 0.875rem;
    }

    .pagination a:hover {
        background: #f9fafb;
        border-color: #3b82f6;
        color: #3b82f6;
    }

    .pagination .active span {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #6b7280;
    }

    .empty-state-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
    }
</style>

<div class="page-header">
    <h1 class="page-title">Complaints & Requests</h1>
    <p class="page-subtitle">Santa Cruz, Laguna - Manage citizen complaints and requests</p>
</div>

<div class="page-actions">
    <a href="{{ route('complaints.create') }}" class="btn-primary">➕ File New Complaint</a>
    
    <form action="{{ route('complaints.index') }}" method="GET" class="filter-group">
        <input type="text" name="search" class="search-input" placeholder="Search by ID, Name, or Subject..." value="{{ request('search') }}">
        
        <select name="status" class="filter-select">
            <option value="">All Status</option>
            <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
            <option value="In Progress" {{ request('status') == 'In Progress' ? 'selected' : '' }}>In Progress</option>
            <option value="Resolved" {{ request('status') == 'Resolved' ? 'selected' : '' }}>Resolved</option>
            <option value="Referred" {{ request('status') == 'Referred' ? 'selected' : '' }}>Referred</option>
            <option value="Closed" {{ request('status') == 'Closed' ? 'selected' : '' }}>Closed</option>
        </select>
        
        <select name="priority" class="filter-select">
            <option value="">All Priority</option>
            <option value="High" {{ request('priority') == 'High' ? 'selected' : '' }}>High</option>
            <option value="Medium" {{ request('priority') == 'Medium' ? 'selected' : '' }}>Medium</option>
            <option value="Low" {{ request('priority') == 'Low' ? 'selected' : '' }}>Low</option>
        </select>

        <button type="submit" class="btn-primary">🔍 Search</button>
    </form>
</div>

<div class="table-container">
    @if($complaints->count() > 0)
    <table>
        <thead>
            <tr>
                <th>Complaint ID</th>
                <th>Name</th>
                <th>Barangay</th>
                <th>Subject</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Date Filed</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($complaints as $complaint)
            <tr>
                <td><strong>{{ $complaint->complaint_id }}</strong></td>
                <td>{{ $complaint->full_name }}</td>
                <td>{{ $complaint->barangay }}</td>
                <td>{{ Str::limit($complaint->subject, 40) }}</td>
                <td>
                    <span class="priority-{{ strtolower($complaint->priority) }}">
                        {{ $complaint->priority }}
                    </span>
                </td>
                <td>
                    <span class="badge badge-{{ strtolower(str_replace(' ', '-', $complaint->status)) }}">
                        {{ $complaint->status }}
                    </span>
                </td>
                <td>{{ $complaint->date_filed->format('M d, Y') }}</td>
                <td>
                    <a href="{{ route('complaints.show', $complaint) }}" class="btn-action">👁️ View</a>
                    <a href="{{ route('complaints.edit', $complaint) }}" class="btn-action">✏️ Edit</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="pagination">
        {{ $complaints->links() }}
    </div>
    @else
    <div class="empty-state">
        <div class="empty-state-icon">📝</div>
        <h3>No Complaints Found</h3>
        <p>Start by filing a new complaint using the button above.</p>
    </div>
    @endif
</div>
@endsection
