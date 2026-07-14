@extends('layouts.app')

@section('title', 'Incoming Concerns - DILG-RC')

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
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border: 2px solid #F4C542;
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
        color: #92400e;
        margin-bottom: 0.25rem;
    }

    .info-banner-desc {
        color: #78350f;
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
    .stat-card-orange { border-left-color: #f59e0b; }

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

    .badge-submitted { background: #fef3c7; color: #92400e; }
    .badge-under-review { background: #dbeafe; color: #1e40af; }

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
    .btn-assign { background: #d1fae5; color: #065f46; }
    .btn-assign:hover { background: #a7f3d0; }

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
    <h1 class="page-title">📥 Incoming Concerns</h1>
    <p class="page-subtitle">Newly submitted concerns from citizens via mobile app</p>
</div>

<!-- Info Banner -->
<div class="info-banner">
    <div class="info-banner-icon">📱</div>
    <div class="info-banner-text">
        <div class="info-banner-title">Mobile App Submissions</div>
        <div class="info-banner-desc">
            These concerns were submitted by citizens through the mobile app and are awaiting review/assignment.
        </div>
    </div>
</div>

<!-- Statistics Row -->
<div class="stats-row">
    <div class="stat-card stat-card-yellow">
        <div class="stat-label">Total Incoming</div>
        <div class="stat-value">{{ $incomingConcerns->total() }}</div>
    </div>
    <div class="stat-card stat-card-blue">
        <div class="stat-label">Submitted</div>
        <div class="stat-value">{{ $incomingConcerns->where('status', 'Submitted')->count() }}</div>
    </div>
    <div class="stat-card stat-card-orange">
        <div class="stat-label">Under Review</div>
        <div class="stat-value">{{ $incomingConcerns->where('status', 'Under Review')->count() }}</div>
    </div>
</div>

<!-- Search and Filters -->
<div class="search-filter-section">
    <form method="GET" action="{{ route('incoming-concerns.index') }}">
        <div class="search-filter-grid">
            <div class="form-group">
                <label class="form-label">Search</label>
                <input 
                    type="text" 
                    name="search" 
                    class="form-input" 
                    placeholder="Search by ID, name, or concern type..."
                    value="{{ request('search') }}"
                >
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
                    <option value="Environmental Concern" {{ request('concern_type') == 'Environmental Concern' ? 'selected' : '' }}>Environmental</option>
                    <option value="Disaster / Risk Concern" {{ request('concern_type') == 'Disaster / Risk Concern' ? 'selected' : '' }}>Disaster / Risk</option>
                    <option value="Road Clearing / Obstruction" {{ request('concern_type') == 'Road Clearing / Obstruction' ? 'selected' : '' }}>Road Clearing</option>
                    <option value="Other" {{ request('concern_type') == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <button type="submit" class="btn-filter">Filter</button>
        </div>
    </form>
</div>

<!-- Incoming Concerns Table -->
<div class="table-container">
    @if($incomingConcerns->count() > 0)
    <table class="data-table">
        <thead>
            <tr>
                <th>Concern ID</th>
                <th>Submitted By</th>
                <th>Contact</th>
                <th>Concern Type</th>
                <th>Status</th>
                <th>Date Submitted</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($incomingConcerns as $concern)
            <tr>
                <td><span class="concern-id">{{ $concern->record_id }}</span></td>
                <td>{{ $concern->full_name }}</td>
                <td>{{ $concern->contact_number }}</td>
                <td>{{ $concern->concern_type }}</td>
                <td>
                    <span class="badge badge-{{ strtolower(str_replace(' ', '-', $concern->status)) }}">
                        {{ $concern->status }}
                    </span>
                </td>
                <td>{{ $concern->date_submitted ? $concern->date_submitted->format('M d, Y g:i A') : 'N/A' }}</td>
                <td>
                    <div class="btn-actions">
                        <a href="{{ route('concerns.show', $concern) }}" class="btn-sm btn-view">View</a>
                        <a href="{{ route('concerns.edit', $concern) }}" class="btn-sm btn-assign">Assign</a>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="pagination">
        {{ $incomingConcerns->links() }}
    </div>
    @else
    <div class="empty-state">
        <div class="empty-state-icon">📭</div>
        <h3 style="margin-bottom: 0.5rem; color: #374151;">No Incoming Concerns</h3>
        <p>There are no new concerns waiting for review. Check back later or monitor the mobile app submissions.</p>
    </div>
    @endif
</div>
@endsection
