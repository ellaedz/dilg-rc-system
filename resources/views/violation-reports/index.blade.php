@extends('layouts.dilg-app')

@section('title', 'Violation Reports - DILG-RC')

@section('content')
<style>
    .filter-bar {
        background: white;
        padding: 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #4b5563;
        margin-bottom: 0.5rem;
    }

    .filter-input, .filter-select {
        padding: 0.625rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.875rem;
    }

    .btn-primary {
        background: var(--dilg-dark-gold);
        color: white;
        padding: 0.625rem 1.25rem;
        border: none;
        border-radius: 0.375rem;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
    }

    .btn-primary:hover {
        background: var(--dilg-yellow);
        color: var(--dilg-dark-gray);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(212, 160, 23, 0.3);
    }

    .btn-filter {
        background: var(--dilg-yellow);
        color: var(--dilg-dark-gray);
        padding: 0.625rem 1.5rem;
        border: none;
        border-radius: 0.375rem;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-filter:hover {
        background: var(--dilg-dark-gold);
        color: white;
    }

    .card {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .table-container {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        background: #f9fafb;
        border-bottom: 2px solid var(--dilg-yellow);
    }

    th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--dilg-dark-gray);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* Ensure status column has enough width */
    th:nth-child(4), td:nth-child(4) {
        min-width: 160px;
    }

    td {
        padding: 1rem;
        font-size: 0.875rem;
        border-bottom: 1px solid #e5e7eb;
    }

    tbody tr:hover {
        background: #fef3c7;
    }

    .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        white-space: nowrap;
    }

    /* Status Badge Colors */
    .badge-submitted {
        background: #3b82f6 !important;
        color: white !important;
    }

    .badge-forverification {
        background: #f59e0b !important;
        color: white !important;
    }

    .badge-verified {
        background: #10b981 !important;
        color: white !important;
    }

    .badge-assigned {
        background: #6366f1 !important;
        color: white !important;
    }

    .badge-inprogress {
        background: #a855f7 !important;
        color: white !important;
    }

    .badge-actiontaken {
        background: #ec4899 !important;
        color: white !important;
    }

    .badge-resolved {
        background: #10b981 !important;
        color: white !important;
    }

    .badge-rejected {
        background: #ef4444 !important;
        color: white !important;
    }

    .badge-closed {
        background: #6b7280 !important;
        color: white !important;
    }

    .btn-action {
        padding: 0.375rem 0.75rem;
        margin: 0 0.25rem;
        border: none;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: all 0.2s;
    }

    .btn-view {
        background: #3b82f6;
        color: white;
    }

    .btn-view:hover {
        background: #2563eb;
    }

    .btn-edit {
        background: #f59e0b;
        color: white;
    }

    .btn-edit:hover {
        background: #d97706;
    }

    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .pagination {
        padding: 1rem;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
    }

    .stats-bar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-card {
        background: white;
        padding: 1rem;
        border-radius: 0.5rem;
        border-left: 4px solid var(--dilg-yellow);
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .stat-value {
        font-size: 1.875rem;
        font-weight: bold;
        color: var(--dilg-dark-gold);
    }

    .stat-label {
        font-size: 0.75rem;
        color: #6b7280;
        text-transform: uppercase;
        margin-top: 0.25rem;
    }
</style>

<div class="page-header mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Violation Reports</h1>
    <p class="text-gray-600 text-sm">Manage all road clearing violation reports from Santa Cruz, Laguna</p>
</div>

<!-- Stats Bar -->
<div class="stats-bar">
    <div class="stat-card">
        <div class="stat-value">{{ $reports->total() }}</div>
        <div class="stat-label">Total Reports</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ \App\Models\ViolationReport::where('status', 'Submitted')->count() }}</div>
        <div class="stat-label">New Submissions</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ \App\Models\ViolationReport::where('status', 'In Progress')->count() }}</div>
        <div class="stat-label">In Progress</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ \App\Models\ViolationReport::where('status', 'Resolved')->count() }}</div>
        <div class="stat-label">Resolved</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="{{ route('violation-reports.index') }}">
        <div class="filter-grid">
            <div class="filter-group">
                <label class="filter-label">Search</label>
                <input type="text" name="search" class="filter-input" placeholder="Report ID, Name..." value="{{ request('search') }}">
            </div>
            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select name="status" class="filter-select">
                    <option value="">All Status</option>
                    <option value="Submitted" {{ request('status') == 'Submitted' ? 'selected' : '' }}>Submitted</option>
                    <option value="For Verification" {{ request('status') == 'For Verification' ? 'selected' : '' }}>For Verification</option>
                    <option value="Verified" {{ request('status') == 'Verified' ? 'selected' : '' }}>Verified</option>
                    <option value="Assigned" {{ request('status') == 'Assigned' ? 'selected' : '' }}>Assigned</option>
                    <option value="In Progress" {{ request('status') == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="Action Taken" {{ request('status') == 'Action Taken' ? 'selected' : '' }}>Action Taken</option>
                    <option value="Resolved" {{ request('status') == 'Resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="Rejected" {{ request('status') == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="Closed" {{ request('status') == 'Closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Violation Type</label>
                <select name="violation_type" class="filter-select">
                    <option value="">All Types</option>
                    <option value="Illegal Parking" {{ request('violation_type') == 'Illegal Parking' ? 'selected' : '' }}>Illegal Parking</option>
                    <option value="Road Obstruction" {{ request('violation_type') == 'Road Obstruction' ? 'selected' : '' }}>Road Obstruction</option>
                    <option value="Sidewalk Obstruction" {{ request('violation_type') == 'Sidewalk Obstruction' ? 'selected' : '' }}>Sidewalk Obstruction</option>
                    <option value="Vending Obstruction" {{ request('violation_type') == 'Vending Obstruction' ? 'selected' : '' }}>Vending Obstruction</option>
                    <option value="Construction Obstruction" {{ request('violation_type') == 'Construction Obstruction' ? 'selected' : '' }}>Construction Obstruction</option>
                    <option value="Encroachment" {{ request('violation_type') == 'Encroachment' ? 'selected' : '' }}>Encroachment</option>
                    <option value="Abandoned Vehicle" {{ request('violation_type') == 'Abandoned Vehicle' ? 'selected' : '' }}>Abandoned Vehicle</option>
                    <option value="Other Road Clearing Violation" {{ request('violation_type') == 'Other Road Clearing Violation' ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Barangay</label>
                <select name="barangay" class="filter-select">
                    <option value="">All Barangays</option>
                    @foreach($barangays as $barangay)
                        <option value="{{ $barangay }}" {{ request('barangay') == $barangay ? 'selected' : '' }}>{{ $barangay }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Apply Filters</button>
    </form>
</div>

<!-- Note: No Manual Entry - Reports come from mobile app only -->

<!-- Reports Table -->
<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Report ID</th>
                    <th>Violation Type</th>
                    <th>Detected Barangay</th>
                    <th>Status</th>
                    <th>Timestamp</th>
                    <th>Assigned Personnel</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $report)
                <tr>
                    <td><strong>{{ $report->report_id }}</strong></td>
                    <td>{{ $report->selected_violation_type }}</td>
                    <td>{{ $report->detected_barangay }}</td>
                    <td><x-status-badge :status="$report->status" /></td>
                    <td>{{ $report->timestamp ? $report->timestamp->format('M d, Y h:i A') : 'N/A' }}</td>
                    <td>@if($report->assigned_personnel) {{ $report->assigned_personnel }} @else <x-status-badge status="Unassigned" size="sm" /> @endif</td>
                    <td>
                        <a href="{{ route('violation-reports.show', $report) }}" class="btn-action btn-view">View</a>
                        {{-- DILG Admin: Read-Only Monitoring (No Edit Access) --}}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #6b7280; padding: 2rem;">
                        No violation reports found. Reports from mobile app will appear here.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        {{ $reports->links() }}
    </div>
</div>
@endsection
