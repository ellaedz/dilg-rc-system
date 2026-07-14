@extends('layouts.app')

@section('title', 'Assigned Concerns - DILG-RC')

@section('content')
<style>
    .page-header {
        margin-bottom: 2rem;
    }

    .page-title {
        font-size: 2rem;
        font-weight: bold;
        color: #333;
        margin-bottom: 0.5rem;
    }

    .page-subtitle {
        color: #6b7280;
        font-size: 0.9375rem;
    }

    .info-banner {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        border: 2px solid #3b82f6;
        border-radius: 0.75rem;
        padding: 1.25rem;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .info-banner-icon {
        font-size: 2rem;
    }

    .info-banner-text {
        flex: 1;
    }

    .info-banner-title {
        font-weight: 600;
        color: #1e3a8a;
        margin-bottom: 0.25rem;
    }

    .info-banner-desc {
        color: #1e40af;
        font-size: 0.9375rem;
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

    .stats-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-card {
        background: white;
        border-radius: 0.75rem;
        padding: 1.25rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border-left: 4px solid;
    }

    .stat-card-yellow { border-left-color: #F4C542; }
    .stat-card-blue { border-left-color: #3b82f6; }
    .stat-card-green { border-left-color: #10b981; }

    .stat-label {
        font-size: 0.875rem;
        color: #6b7280;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: bold;
        color: #333;
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

    .concern-id {
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

    .badge-assigned { background: #fef3c7; color: #92400e; }
    .badge-in-progress { background: #bfdbfe; color: #1e3a8a; }

    .personnel-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background: #e0e7ff;
        color: #3730a3;
        border-radius: 1rem;
        font-size: 0.8125rem;
        font-weight: 500;
    }

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
    .btn-update { background: #fef3c7; color: #92400e; }
    .btn-update:hover { background: #fde68a; }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #6b7280;
    }

    .empty-state-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        padding: 1.5rem;
    }
</style>

<div class="page-header">
    <h1 class="page-title">👥 Assigned Concerns</h1>
    <p class="page-subtitle">Concerns currently assigned to staff members and being worked on</p>
</div>

<!-- Info Banner -->
<div class="info-banner">
    <div class="info-banner-icon">👷</div>
    <div class="info-banner-text">
        <div class="info-banner-title">Active Work Items</div>
        <div class="info-banner-desc">
            These concerns have been assigned to personnel and are currently being processed or resolved.
        </div>
    </div>
</div>

<!-- Statistics Row -->
<div class="stats-row">
    <div class="stat-card stat-card-yellow">
        <div class="stat-label">Total Assigned</div>
        <div class="stat-value">{{ $assignedConcerns->total() }}</div>
    </div>
    <div class="stat-card stat-card-blue">
        <div class="stat-label">Assigned (New)</div>
        <div class="stat-value">{{ $assignedConcerns->where('status', 'Assigned')->count() }}</div>
    </div>
    <div class="stat-card stat-card-green">
        <div class="stat-label">In Progress</div>
        <div class="stat-value">{{ $assignedConcerns->where('status', 'In Progress')->count() }}</div>
    </div>
</div>

<!-- Search and Filters -->
<div class="search-filter-section">
    <form method="GET" action="{{ route('assigned-concerns.index') }}">
        <div class="search-filter-grid">
            <div class="form-group">
                <label class="form-label">Search</label>
                <input 
                    type="text" 
                    name="search" 
                    class="form-input" 
                    placeholder="Search by ID, name, or assigned personnel..."
                    value="{{ request('search') }}"
                >
            </div>
            <div class="form-group">
                <label class="form-label">Assigned Personnel</label>
                <input 
                    type="text" 
                    name="assigned_personnel" 
                    class="form-input" 
                    placeholder="Personnel name..."
                    value="{{ request('assigned_personnel') }}"
                >
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Assigned" {{ request('status') == 'Assigned' ? 'selected' : '' }}>Assigned</option>
                    <option value="In Progress" {{ request('status') == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                </select>
            </div>
            <button type="submit" class="btn-filter">Filter</button>
        </div>
    </form>
</div>

<!-- Assigned Concerns Table -->
<div class="table-container">
    @if($assignedConcerns->count() > 0)
    <table class="data-table">
        <thead>
            <tr>
                <th>Concern ID</th>
                <th>Submitted By</th>
                <th>Concern Type</th>
                <th>Assigned To</th>
                <th>Status</th>
                <th>Date Assigned</th>
                <th>Last Updated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($assignedConcerns as $concern)
            <tr>
                <td><span class="concern-id">{{ $concern->record_id }}</span></td>
                <td>{{ $concern->full_name }}</td>
                <td>{{ $concern->concern_type }}</td>
                <td>
                    <span class="personnel-badge">
                        {{ $concern->assigned_personnel }}
                    </span>
                </td>
                <td>
                    <span class="badge badge-{{ strtolower(str_replace(' ', '-', $concern->status)) }}">
                        {{ $concern->status }}
                    </span>
                </td>
                <td>{{ $concern->created_at->format('M d, Y') }}</td>
                <td>{{ $concern->updated_at->format('M d, Y g:i A') }}</td>
                <td>
                    <div class="btn-actions">
                        <a href="{{ route('concerns.show', $concern) }}" class="btn-sm btn-view">View</a>
                        <a href="{{ route('concerns.edit', $concern) }}" class="btn-sm btn-update">Update</a>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="pagination">
        {{ $assignedConcerns->links() }}
    </div>
    @else
    <div class="empty-state">
        <div class="empty-state-icon">📋</div>
        <h3 style="margin-bottom: 0.5rem; color: #374151;">No Assigned Concerns</h3>
        <p>There are no concerns currently assigned to staff. Assign concerns from the "Incoming Concerns" module.</p>
    </div>
    @endif
</div>
@endsection
