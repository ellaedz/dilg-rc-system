@extends('layouts.app')

@section('title', 'Complaint Details - DILG-RC System')

@section('content')
<style>
    .record-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 2rem;
    }

    .complaint-id-badge {
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        font-size: 1.25rem;
        font-weight: bold;
        display: inline-block;
    }

    .record-actions {
        display: flex;
        gap: 0.75rem;
    }

    .btn-primary, .btn-secondary, .btn-danger {
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
        border: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        color: white;
    }

    .btn-secondary {
        background: white;
        color: #374151;
        border: 1px solid #d1d5db;
    }

    .btn-danger {
        background: #dc2626;
        color: white;
    }

    .btn-primary:hover, .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .btn-secondary:hover {
        background: #f9fafb;
        border-color: #3b82f6;
        color: #3b82f6;
    }

    .details-container {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .card {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        padding: 1.5rem;
    }

    .card-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #e5e7eb;
    }

    .info-grid {
        display: grid;
        gap: 1.25rem;
    }

    .info-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .info-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .info-value {
        font-size: 0.9375rem;
        color: #1f2937;
    }

    .info-value-large {
        font-size: 1rem;
        line-height: 1.6;
    }

    .badge {
        display: inline-block;
        padding: 0.375rem 0.875rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .badge-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-in-progress {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-resolved {
        background: #d1fae5;
        color: #065f46;
    }

    .badge-referred {
        background: #e0e7ff;
        color: #3730a3;
    }

    .badge-closed {
        background: #e5e7eb;
        color: #374151;
    }

    .priority-badge {
        display: inline-block;
        padding: 0.375rem 0.875rem;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .priority-high {
        background: #fee2e2;
        color: #991b1b;
    }

    .priority-medium {
        background: #fef3c7;
        color: #92400e;
    }

    .priority-low {
        background: #d1fae5;
        color: #065f46;
    }

    .concern-badge {
        display: inline-block;
        padding: 0.375rem 0.875rem;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 600;
        background: #f3f4f6;
        color: #374151;
    }

    .empty-remarks {
        color: #9ca3af;
        font-style: italic;
    }

    .timeline {
        list-style: none;
        padding: 0;
        margin: 0;
        position: relative;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 8px;
        bottom: 8px;
        width: 2px;
        background: #e5e7eb;
    }

    .timeline-item {
        position: relative;
        padding-left: 2rem;
        padding-bottom: 1.5rem;
    }

    .timeline-item:last-child {
        padding-bottom: 0;
    }

    .timeline-dot {
        position: absolute;
        left: 0;
        top: 0;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #3b82f6;
        border: 3px solid white;
        box-shadow: 0 0 0 2px #e5e7eb;
    }

    .timeline-date {
        font-size: 0.75rem;
        color: #6b7280;
        margin-bottom: 0.25rem;
    }

    .timeline-content {
        font-size: 0.875rem;
        color: #1f2937;
    }
</style>

<div class="page-header">
    <h1 class="page-title">Complaint/Request Details</h1>
    <p class="page-subtitle">View complete complaint information</p>
</div>

<div class="record-header">
    <div class="complaint-id-badge">{{ $complaint->complaint_id }}</div>
    <div class="record-actions">
        <a href="{{ route('complaints.edit', $complaint) }}" class="btn-primary">✏️ Edit</a>
        <a href="{{ route('complaints.index') }}" class="btn-secondary">← Back to List</a>
        <form action="{{ route('complaints.destroy', $complaint) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this complaint?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-danger">🗑️ Delete</button>
        </form>
    </div>
</div>

<div class="details-container">
    <!-- Main Information -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Personal Information -->
        <div class="card">
            <h3 class="card-title">Personal Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value">{{ $complaint->full_name }}</div>
                </div>

                <div class="info-item">
                    <div class="info-label">Contact Number</div>
                    <div class="info-value">{{ $complaint->contact_number }}</div>
                </div>

                <div class="info-item">
                    <div class="info-label">Location</div>
                    <div class="info-value">{{ $complaint->barangay }}, {{ $complaint->municipality }}, {{ $complaint->province }}</div>
                </div>

                <div class="info-item">
                    <div class="info-label">Complete Address</div>
                    <div class="info-value info-value-large">{{ $complaint->address }}</div>
                </div>
            </div>
        </div>

        <!-- Complaint Information -->
        <div class="card">
            <h3 class="card-title">Complaint/Request Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Type of Concern</div>
                    <div class="info-value">
                        <span class="concern-badge">{{ $complaint->concern_type }}</span>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Subject</div>
                    <div class="info-value info-value-large">{{ $complaint->subject }}</div>
                </div>

                <div class="info-item">
                    <div class="info-label">Detailed Description</div>
                    <div class="info-value info-value-large">{{ $complaint->description }}</div>
                </div>

                <div class="info-item">
                    <div class="info-label">Remarks</div>
                    <div class="info-value info-value-large">
                        @if($complaint->remarks)
                            {{ $complaint->remarks }}
                        @else
                            <span class="empty-remarks">No remarks added</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignment Information -->
        <div class="card">
            <h3 class="card-title">Assignment Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Assigned Office</div>
                    <div class="info-value">
                        @if($complaint->assigned_office)
                            {{ $complaint->assigned_office }}
                        @else
                            <span class="empty-remarks">Not assigned</span>
                        @endif
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Assigned Personnel</div>
                    <div class="info-value">
                        @if($complaint->assigned_personnel)
                            {{ $complaint->assigned_personnel }}
                        @else
                            <span class="empty-remarks">Not assigned</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Status Card -->
        <div class="card">
            <h3 class="card-title">Status & Priority</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Current Status</div>
                    <div class="info-value">
                        <span class="badge badge-{{ strtolower(str_replace(' ', '-', $complaint->status)) }}">
                            {{ $complaint->status }}
                        </span>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Priority Level</div>
                    <div class="info-value">
                        <span class="priority-badge priority-{{ strtolower($complaint->priority) }}">
                            @if($complaint->priority == 'High') 🔴 @elseif($complaint->priority == 'Medium') 🟡 @else 🟢 @endif {{ $complaint->priority }}
                        </span>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Date Filed</div>
                    <div class="info-value">{{ $complaint->date_filed->format('F d, Y') }}</div>
                </div>

                <div class="info-item">
                    <div class="info-label">Created</div>
                    <div class="info-value">{{ $complaint->created_at->format('M d, Y h:i A') }}</div>
                </div>

                <div class="info-item">
                    <div class="info-label">Last Updated</div>
                    <div class="info-value">{{ $complaint->updated_at->format('M d, Y h:i A') }}</div>
                </div>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="card">
            <h3 class="card-title">Activity Timeline</h3>
            <ul class="timeline">
                <li class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-date">{{ $complaint->created_at->format('M d, Y h:i A') }}</div>
                    <div class="timeline-content">Complaint filed with status: <strong>{{ $complaint->status }}</strong></div>
                </li>
                @if($complaint->updated_at != $complaint->created_at)
                <li class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-date">{{ $complaint->updated_at->format('M d, Y h:i A') }}</div>
                    <div class="timeline-content">Complaint updated</div>
                </li>
                @endif
            </ul>
        </div>
    </div>
</div>
@endsection
