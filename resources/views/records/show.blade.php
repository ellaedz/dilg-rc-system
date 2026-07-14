@extends('layouts.app')

@section('title', 'View Record - DILG-RC')

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

    .btn-secondary {
        background: #6b7280;
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }

    .btn-secondary:hover {
        background: #4b5563;
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
        margin-left: 0.5rem;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(212, 160, 23, 0.4);
    }

    .content-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
    }

    .card {
        background: white;
        border-radius: 0.75rem;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .card-header {
        font-size: 1.25rem;
        font-weight: bold;
        color: #333;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #F4C542;
    }

    .detail-row {
        display: grid;
        grid-template-columns: 150px 1fr;
        gap: 1rem;
        padding: 0.875rem 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-weight: 600;
        color: #374151;
    }

    .detail-value {
        color: #4b5563;
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

    /* Workflow Timeline */
    .timeline {
        position: relative;
        padding-left: 2.5rem;
    }

    .timeline-item {
        position: relative;
        padding-bottom: 2rem;
    }

    .timeline-item:last-child {
        padding-bottom: 0;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -2.5rem;
        top: 0.5rem;
        width: 2px;
        height: calc(100% + 1rem);
        background: #e5e7eb;
    }

    .timeline-item:last-child::before {
        display: none;
    }

    .timeline-icon {
        position: absolute;
        left: -3rem;
        top: 0;
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        z-index: 1;
    }

    .timeline-icon.active {
        background: #F4C542;
        border: 3px solid #FEF3C7;
    }

    .timeline-icon.completed {
        background: #10b981;
        border: 3px solid #d1fae5;
    }

    .timeline-icon.pending {
        background: #e5e7eb;
        border: 3px solid #f3f4f6;
    }

    .timeline-content {
        background: #f9fafb;
        padding: 1rem;
        border-radius: 0.5rem;
        border-left: 3px solid #F4C542;
    }

    .timeline-title {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.25rem;
    }

    .timeline-date {
        font-size: 0.8125rem;
        color: #6b7280;
    }
</style>

<div class="page-header">
    <h1 class="page-title">📁 Record Details</h1>
    <div>
        <a href="{{ route('records.index') }}" class="btn-secondary">← Back to List</a>
        <a href="{{ route('records.edit', $record) }}" class="btn-primary">Edit Record</a>
    </div>
</div>

<div class="content-grid">
    <!-- Left Column: Record Information -->
    <div>
        <!-- Basic Information Card -->
        <div class="card" style="margin-bottom: 1.5rem;">
            <div class="card-header">Record Information</div>
            
            <div class="detail-row">
                <div class="detail-label">Record ID:</div>
                <div class="detail-value"><strong style="color: #D4A017;">{{ $record->record_id }}</strong></div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div class="detail-value">
                    <span class="badge badge-{{ strtolower(str_replace(' ', '-', $record->status)) }}">
                        {{ $record->status }}
                    </span>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Full Name:</div>
                <div class="detail-value">{{ $record->full_name }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Contact Number:</div>
                <div class="detail-value">{{ $record->contact_number }}</div>
            </div>

            @if($record->email)
            <div class="detail-row">
                <div class="detail-label">Email:</div>
                <div class="detail-value">{{ $record->email }}</div>
            </div>
            @endif

            <div class="detail-row">
                <div class="detail-label">Address:</div>
                <div class="detail-value">{{ $record->address }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Concern Type:</div>
                <div class="detail-value"><strong>{{ $record->concern_type }}</strong></div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Description:</div>
                <div class="detail-value">{{ $record->description }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Date Submitted:</div>
                <div class="detail-value">{{ $record->date_submitted ? $record->date_submitted->format('F d, Y g:i A') : 'N/A' }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Last Updated:</div>
                <div class="detail-value">{{ $record->updated_at->format('F d, Y g:i A') }}</div>
            </div>
        </div>

        <!-- Assignment Information Card -->
        <div class="card">
            <div class="card-header">Assignment Information</div>
            
            <div class="detail-row">
                <div class="detail-label">Assigned Office:</div>
                <div class="detail-value">{{ $record->assigned_office ?? 'Not assigned' }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Assigned Personnel:</div>
                <div class="detail-value">{{ $record->assigned_personnel ?? 'Not assigned' }}</div>
            </div>

            @if($record->remarks)
            <div class="detail-row">
                <div class="detail-label">Remarks:</div>
                <div class="detail-value">{{ $record->remarks }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Right Column: Workflow Timeline -->
    <div>
        <div class="card">
            <div class="card-header">Workflow Timeline</div>
            
            <div class="timeline">
                <!-- Record Created -->
                <div class="timeline-item">
                    <div class="timeline-icon completed">✓</div>
                    <div class="timeline-content">
                        <div class="timeline-title">Record Created</div>
                        <div class="timeline-date">{{ $record->created_at->format('M d, Y g:i A') }}</div>
                    </div>
                </div>

                <!-- Under Review -->
                <div class="timeline-item">
                    <div class="timeline-icon {{ $record->status == 'Pending' ? 'pending' : 'completed' }}">
                        {{ $record->status == 'Pending' ? '○' : '✓' }}
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-title">Under Review</div>
                        <div class="timeline-date">
                            {{ $record->status != 'Pending' ? $record->updated_at->format('M d, Y g:i A') : 'Pending' }}
                        </div>
                    </div>
                </div>

                <!-- Assigned -->
                <div class="timeline-item">
                    <div class="timeline-icon {{ in_array($record->status, ['Pending', 'Under Review']) ? 'pending' : ($record->status == 'In Progress' ? 'active' : 'completed') }}">
                        {{ in_array($record->status, ['Pending', 'Under Review']) ? '○' : ($record->status == 'In Progress' ? '●' : '✓') }}
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-title">Assigned to Personnel</div>
                        <div class="timeline-date">
                            {{ $record->assigned_personnel ? ($record->status != 'Pending' ? $record->updated_at->format('M d, Y g:i A') : 'Assigned') : 'Pending assignment' }}
                        </div>
                    </div>
                </div>

                <!-- In Progress -->
                <div class="timeline-item">
                    <div class="timeline-icon {{ $record->status == 'In Progress' ? 'active' : (in_array($record->status, ['Resolved', 'Closed']) ? 'completed' : 'pending') }}">
                        {{ $record->status == 'In Progress' ? '●' : (in_array($record->status, ['Resolved', 'Closed']) ? '✓' : '○') }}
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-title">In Progress</div>
                        <div class="timeline-date">
                            {{ $record->status == 'In Progress' ? 'Currently in progress' : (in_array($record->status, ['Resolved', 'Closed']) ? $record->updated_at->format('M d, Y g:i A') : 'Pending') }}
                        </div>
                    </div>
                </div>

                <!-- Resolved -->
                <div class="timeline-item">
                    <div class="timeline-icon {{ in_array($record->status, ['Resolved', 'Closed']) ? 'completed' : 'pending' }}">
                        {{ in_array($record->status, ['Resolved', 'Closed']) ? '✓' : '○' }}
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-title">Resolved</div>
                        <div class="timeline-date">
                            {{ $record->status == 'Resolved' ? $record->updated_at->format('M d, Y g:i A') : ($record->status == 'Closed' ? 'Resolved and closed' : 'Pending resolution') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
