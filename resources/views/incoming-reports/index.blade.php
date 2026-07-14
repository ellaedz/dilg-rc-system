@extends('layouts.app')

@section('title', 'Incoming Reports - DILG-RC')

@section('content')
<style>
    .stats-bar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 0.5rem;
        border-left: 4px solid var(--dilg-yellow);
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: bold;
        color: var(--dilg-dark-gold);
    }

    .stat-label {
        font-size: 0.875rem;
        color: #6b7280;
        text-transform: uppercase;
        margin-top: 0.5rem;
    }

    .reports-grid {
        display: grid;
        gap: 1.5rem;
    }

    .report-card {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: all 0.3s;
    }

    .report-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }

    .report-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem;
        background: #fef3c7;
        border-bottom: 3px solid var(--dilg-yellow);
    }

    .report-id {
        font-size: 1.125rem;
        font-weight: bold;
        color: var(--dilg-dark-gray);
    }

    .report-time {
        font-size: 0.75rem;
        color: #6b7280;
    }

    .report-body {
        padding: 1.5rem;
    }

    .report-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
    }

    .report-main {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .report-field {
        display: flex;
        gap: 0.5rem;
    }

    .field-label {
        font-weight: 500;
        color: #6b7280;
        min-width: 120px;
        font-size: 0.875rem;
    }

    .field-value {
        color: var(--dilg-dark-gray);
        font-size: 0.875rem;
    }

    .report-description {
        background: #f9fafb;
        padding: 1rem;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        color: #374151;
        line-height: 1.6;
    }

    .report-sidebar {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .photo-preview {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 0.375rem;
        border: 2px solid #e5e7eb;
    }

    .no-photo {
        width: 100%;
        height: 200px;
        background: #f3f4f6;
        border-radius: 0.375rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        font-size: 0.875rem;
        border: 2px dashed #d1d5db;
    }

    .gps-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        background: #dbeafe;
        color: #1e40af;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .gps-badge.missing {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge {
        display: inline-block;
        padding: 0.375rem 0.875rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .badge-submitted {
        background: #fef3c7;
        color: #92400e;
    }

    .report-actions {
        display: flex;
        gap: 0.75rem;
        padding: 1.5rem;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
    }

    .btn-verify {
        flex: 1;
        background: var(--dilg-success);
        color: white;
        padding: 0.75rem;
        border: none;
        border-radius: 0.375rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-verify:hover {
        background: #059669;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .btn-reject {
        flex: 1;
        background: var(--dilg-error);
        color: white;
        padding: 0.75rem;
        border: none;
        border-radius: 0.375rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-reject:hover {
        background: #dc2626;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    .btn-view {
        background: #3b82f6;
        color: white;
        padding: 0.75rem 1rem;
        border: none;
        border-radius: 0.375rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
    }

    .btn-view:hover {
        background: #2563eb;
    }

    .alert-info {
        background: #dbeafe;
        border-left: 4px solid #3b82f6;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
        color: #1e40af;
        font-size: 0.875rem;
    }

    .empty-state {
        background: white;
        padding: 4rem 2rem;
        border-radius: 0.5rem;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .empty-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
    }

    .empty-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--dilg-dark-gray);
        margin-bottom: 0.5rem;
    }

    .empty-text {
        color: #6b7280;
        font-size: 0.875rem;
    }

    @media (max-width: 768px) {
        .report-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="page-header">
    <h1 class="page-title">📥 Incoming Reports</h1>
    <p class="page-subtitle">New violation reports submitted from mobile app awaiting verification</p>
</div>

<!-- Stats Bar -->
<div class="stats-bar">
    <div class="stat-card">
        <div class="stat-value">{{ $reports->total() }}</div>
        <div class="stat-label">New Submissions</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ $reports->where('latitude', '!=', null)->count() }}</div>
        <div class="stat-label">With GPS</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ $reports->where('photo_path', '!=', null)->count() }}</div>
        <div class="stat-label">With Photo</div>
    </div>
</div>

<div class="alert-info">
    ℹ️ <strong>Verification Required:</strong> Review each report and verify if it's a valid road clearing violation. 
    Check photo evidence, GPS location, and barangay assignment before verification.
</div>

<!-- Reports Grid -->
<div class="reports-grid">
    @forelse($reports as $report)
    <div class="report-card">
        <div class="report-header">
            <div>
                <div class="report-id">{{ $report->report_id }}</div>
                <div class="report-time">
                    ⏰ {{ $report->timestamp ? $report->timestamp->format('M d, Y h:i A') : 'N/A' }}
                </div>
            </div>
            <span class="badge badge-submitted">{{ $report->status }}</span>
        </div>

        <div class="report-body">
            <div class="report-grid">
                <div class="report-main">
                    <div class="report-field">
                        <span class="field-label">Submitted By:</span>
                        <span class="field-value"><strong>{{ $report->submitted_by }}</strong></span>
                    </div>

                    <div class="report-field">
                        <span class="field-label">Contact:</span>
                        <span class="field-value">{{ $report->contact_number }}</span>
                    </div>

                    <div class="report-field">
                        <span class="field-label">Violation Type:</span>
                        <span class="field-value"><strong>{{ $report->selected_violation_type }}</strong></span>
                    </div>

                    <div class="report-field">
                        <span class="field-label">Detected Barangay:</span>
                        <span class="field-value"><strong>{{ $report->detected_barangay }}</strong></span>
                    </div>

                    <div class="report-field">
                        <span class="field-label">GPS Status:</span>
                        <span class="field-value">
                            @if($report->latitude && $report->longitude)
                                <span class="gps-badge">
                                    📍 {{ $report->latitude }}, {{ $report->longitude }}
                                </span>
                            @else
                                <span class="gps-badge missing">❌ No GPS Data</span>
                            @endif
                        </span>
                    </div>

                    <div style="margin-top: 0.5rem;">
                        <div class="field-label" style="margin-bottom: 0.5rem;">Description:</div>
                        <div class="report-description">{{ $report->description }}</div>
                    </div>
                </div>

                <div class="report-sidebar">
                    @if($report->photo_path)
                        <img src="{{ asset('storage/' . $report->photo_path) }}" alt="Violation Photo" class="photo-preview">
                    @else
                        <div class="no-photo">📷 No Photo</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="report-actions">
            <a href="{{ route('violation-reports.show', $report) }}" class="btn-view">👁️ View Details</a>
            <form action="{{ route('incoming-reports.verify', $report) }}" method="POST" style="flex: 1;">
                @csrf
                <button type="submit" class="btn-verify">✅ Verify Report</button>
            </form>
            <form action="{{ route('incoming-reports.reject', $report) }}" method="POST" style="flex: 1;" 
                  onsubmit="return confirm('Are you sure you want to reject this report?');">
                @csrf
                <button type="submit" class="btn-reject">❌ Reject</button>
            </form>
        </div>
    </div>
    @empty
    <div class="empty-state">
        <div class="empty-icon">📭</div>
        <div class="empty-title">No Incoming Reports</div>
        <div class="empty-text">All reports have been processed. New submissions from the mobile app will appear here.</div>
    </div>
    @endforelse
</div>

@if($reports->hasPages())
<div style="margin-top: 2rem; display: flex; justify-content: center;">
    {{ $reports->links() }}
</div>
@endif

@endsection
