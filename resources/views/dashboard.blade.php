@extends('layouts.app')

@section('title', 'Dashboard - DILG-RC System')

@section('content')
<style>
    .dashboard-header {
        margin-bottom: 2rem;
    }

    .dashboard-title {
        font-size: 2rem;
        font-weight: bold;
        color: #333;
        margin-bottom: 0.5rem;
    }

    .dashboard-subtitle {
        color: #666;
        font-size: 0.9375rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }

    .stat-card {
        background: white;
        border-radius: 0.75rem;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.3s;
        border-left: 5px solid;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
    }

    .stat-card-yellow {
        border-left-color: #F4C542;
    }

    .stat-card-orange {
        border-left-color: #f59e0b;
    }

    .stat-card-blue {
        border-left-color: #3b82f6;
    }

    .stat-card-green {
        border-left-color: #10b981;
    }

    .stat-card-gold {
        border-left-color: #D4A017;
    }

    .stat-card-dark-gold {
        border-left-color: #b8860b;
    }

    .stat-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .stat-icon {
        font-size: 2.5rem;
        opacity: 0.9;
    }

    .stat-info {
        flex: 1;
    }

    .stat-label {
        font-size: 0.875rem;
        color: #6b7280;
        margin-bottom: 0.5rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-value {
        font-size: 2.25rem;
        font-weight: bold;
        color: #333;
    }

    .stat-footer {
        margin-top: 0.75rem;
        font-size: 0.8125rem;
        color: #9ca3af;
    }

    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-not-connected {
        background: #fee2e2;
        color: #991b1b;
    }

    /* Activity Section */
    .activity-section {
        background: white;
        border-radius: 0.75rem;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .activity-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #F4C542;
    }

    .activity-title {
        font-size: 1.5rem;
        font-weight: bold;
        color: #333;
    }

    .activity-table {
        width: 100%;
        border-collapse: collapse;
    }

    .activity-table th {
        background: #f9fafb;
        padding: 0.875rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .activity-table td {
        padding: 1rem 0.875rem;
        border-bottom: 1px solid #e5e7eb;
        font-size: 0.9375rem;
    }

    .activity-table tr:last-child td {
        border-bottom: none;
    }

    .activity-table tr:hover {
        background: #fefce8;
    }

    .activity-id {
        font-weight: 600;
        color: #D4A017;
    }

    .activity-desc {
        color: #4b5563;
    }

    .activity-status {
        display: inline-block;
        padding: 0.375rem 0.875rem;
        border-radius: 1rem;
        font-size: 0.8125rem;
        font-weight: 600;
    }

    .badge-submitted { background: #fef3c7; color: #92400e; }
    .badge-forverification { background: #fef3c7; color: #92400e; }
    .badge-for-verification { background: #fef3c7; color: #92400e; }
    .badge-verified { background: #d1fae5; color: #065f46; }
    .badge-assigned { background: #e0e7ff; color: #3730a3; }
    .badge-in-progress { background: #bfdbfe; color: #1e3a8a; }
    .badge-inprogress { background: #bfdbfe; color: #1e3a8a; }
    .badge-action-taken { background: #ddd6fe; color: #5b21b6; }
    .badge-actiontaken { background: #ddd6fe; color: #5b21b6; }
    .badge-resolved { background: #d1fae5; color: #065f46; }
    .badge-rejected { background: #fee2e2; color: #991b1b; }
    .badge-closed { background: #e5e7eb; color: #374151; }
</style>

<div class="dashboard-header">
    <h1 class="dashboard-title">📊 Dashboard</h1>
    <p class="dashboard-subtitle">Welcome to DILG-RC Road Clearing Violation Reporting System - Santa Cruz, Laguna</p>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <!-- Total Violation Reports -->
    <div class="stat-card stat-card-yellow">
        <div class="stat-header">
            <div class="stat-info">
                <div class="stat-label">Total Violation Reports</div>
                <div class="stat-value">{{ number_format($stats['total_reports']) }}</div>
            </div>
            <div class="stat-icon">⚠️</div>
        </div>
        <div class="stat-footer">All submitted reports</div>
    </div>

    <!-- New Submissions -->
    <div class="stat-card stat-card-orange">
        <div class="stat-header">
            <div class="stat-info">
                <div class="stat-label">New Submissions</div>
                <div class="stat-value">{{ number_format($stats['new_submissions']) }}</div>
            </div>
            <div class="stat-icon">📥</div>
        </div>
        <div class="stat-footer">From mobile app</div>
    </div>

    <!-- For Verification -->
    <div class="stat-card stat-card-orange">
        <div class="stat-header">
            <div class="stat-info">
                <div class="stat-label">For Verification</div>
                <div class="stat-value">{{ number_format($stats['for_verification']) }}</div>
            </div>
            <div class="stat-icon">🔍</div>
        </div>
        <div class="stat-footer">Awaiting verification</div>
    </div>

    <!-- Verified Reports -->
    <div class="stat-card stat-card-green">
        <div class="stat-header">
            <div class="stat-info">
                <div class="stat-label">Verified Reports</div>
                <div class="stat-value">{{ number_format($stats['verified']) }}</div>
            </div>
            <div class="stat-icon">✅</div>
        </div>
        <div class="stat-footer">Valid violations</div>
    </div>

    <!-- Assigned -->
    <div class="stat-card stat-card-blue">
        <div class="stat-header">
            <div class="stat-info">
                <div class="stat-label">Assigned</div>
                <div class="stat-value">{{ number_format($stats['assigned']) }}</div>
            </div>
            <div class="stat-icon">👥</div>
        </div>
        <div class="stat-footer">Assigned to personnel</div>
    </div>

    <!-- In Progress -->
    <div class="stat-card stat-card-blue">
        <div class="stat-header">
            <div class="stat-info">
                <div class="stat-label">In Progress</div>
                <div class="stat-value">{{ number_format($stats['in_progress']) }}</div>
            </div>
            <div class="stat-icon">🔄</div>
        </div>
        <div class="stat-footer">Currently processing</div>
    </div>

    <!-- Action Taken -->
    <div class="stat-card stat-card-blue">
        <div class="stat-header">
            <div class="stat-info">
                <div class="stat-label">Action Taken</div>
                <div class="stat-value">{{ number_format($stats['action_taken']) }}</div>
            </div>
            <div class="stat-icon">⚡</div>
        </div>
        <div class="stat-footer">Actions completed</div>
    </div>

    <!-- Resolved Cases -->
    <div class="stat-card stat-card-green">
        <div class="stat-header">
            <div class="stat-info">
                <div class="stat-label">Resolved</div>
                <div class="stat-value">{{ number_format($stats['resolved']) }}</div>
            </div>
            <div class="stat-icon">✅</div>
        </div>
        <div class="stat-footer">Successfully resolved</div>
    </div>

    <!-- Top Violation Type -->
    <div class="stat-card stat-card-gold">
        <div class="stat-header">
            <div class="stat-info">
                <div class="stat-label">Top Violation Type</div>
                <div class="stat-value" style="font-size: 1.25rem;">{{ $stats['top_violation_type'] }}</div>
            </div>
            <div class="stat-icon">🏆</div>
        </div>
        <div class="stat-footer">{{ $stats['top_violation_count'] }} reports</div>
    </div>

    <!-- Top Barangay -->
    <div class="stat-card stat-card-gold">
        <div class="stat-header">
            <div class="stat-info">
                <div class="stat-label">Top Barangay</div>
                <div class="stat-value" style="font-size: 1.25rem;">{{ $stats['top_barangay'] }}</div>
            </div>
            <div class="stat-icon">📍</div>
        </div>
        <div class="stat-footer">{{ $stats['top_barangay_count'] }} reports</div>
    </div>

    <!-- Dataset Status -->
    <div class="stat-card stat-card-dark-gold">
        <div class="stat-header">
            <div class="stat-info">
                <div class="stat-label">Dataset Module</div>
                <span class="status-badge status-not-connected">{{ $stats['dataset_status'] }}</span>
            </div>
            <div class="stat-icon">📊</div>
        </div>
        <div class="stat-footer">Data management system (Phase 3)</div>
    </div>

    <!-- AI Model Status -->
    <div class="stat-card stat-card-dark-gold">
        <div class="stat-header">
            <div class="stat-info">
                <div class="stat-label">AI Model Status</div>
                <span class="status-badge status-not-connected">{{ $stats['ai_model_status'] }}</span>
            </div>
            <div class="stat-icon">🤖</div>
        </div>
        <div class="stat-footer">ML & NLP engine (Phase 3)</div>
    </div>
</div>

<!-- Recent Activity -->
<div class="activity-section">
    <div class="activity-header">
        <h2 class="activity-title">Recent Activity</h2>
    </div>

    <table class="activity-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Description</th>
                <th>Status</th>
                <th>Date & Time</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentActivities as $activity)
            <tr>
                <td><span class="activity-id">{{ $activity['id'] }}</span></td>
                <td><span class="activity-desc">{{ $activity['description'] }}</span></td>
                <td>
                    <span class="activity-status status-{{ strtolower(str_replace(' ', '-', $activity['status'])) }}">
                        {{ $activity['status'] }}
                    </span>
                </td>
                <td>{{ $activity['date'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
