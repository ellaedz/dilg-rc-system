@props(['timelines', 'currentStatus'])

<style>
    .status-timeline-container {
        position: relative;
        padding: 0.5rem 0;
    }

    .timeline-vertical {
        position: relative;
        padding-left: 3rem;
    }

    .timeline-vertical::before {
        content: '';
        position: absolute;
        left: 1.125rem;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e5e7eb;
    }

    .timeline-entry {
        position: relative;
        padding-bottom: 1.5rem;
        animation: fadeInUp 0.4s ease-out;
    }

    .timeline-entry:last-child {
        padding-bottom: 0;
    }

    .timeline-icon {
        position: absolute;
        left: -3rem;
        top: 0.125rem;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        z-index: 2;
        color: white;
        border: 3px solid white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    /* Status-specific colors for circles */
    .timeline-icon.status-submitted { background: #9ca3af; }
    .timeline-icon.status-forverification { background: #f59e0b; }
    .timeline-icon.status-verified { background: #3b82f6; }
    .timeline-icon.status-assigned { background: #8b5cf6; }
    .timeline-icon.status-inprogress { background: #a855f7; }
    .timeline-icon.status-actiontaken { background: #06b6d4; }
    .timeline-icon.status-resolved { background: #10b981; }
    .timeline-icon.status-rejected { background: #ef4444; }
    .timeline-icon.status-closed { background: #f59e0b; }

    .timeline-icon.current {
        animation: pulse 2s infinite;
    }

    .timeline-content {
        padding: 0;
    }

    .timeline-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.875rem;
        border-radius: 0.375rem;
        font-weight: 600;
        font-size: 0.9375rem;
        margin-bottom: 0.5rem;
    }

    /* Badge colors matching dropdown */
    .badge-submitted { background: #f3f4f6; color: #6b7280; }
    .badge-forverification { background: #fef3c7; color: #92400e; }
    .badge-verified { background: #dbeafe; color: #1e40af; }
    .badge-assigned { background: #e0e7ff; color: #3730a3; }
    .badge-inprogress { background: #ddd6fe; color: #5b21b6; }
    .badge-actiontaken { background: #ccfbf1; color: #115e59; }
    .badge-resolved { background: #d1fae5; color: #065f46; }
    .badge-rejected { background: #fee2e2; color: #991b1b; }
    .badge-closed { background: #fef9c3; color: #854d0e; }

    .timeline-badge-current {
        display: inline-block;
        padding: 0.125rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        background: #3b82f6;
        color: white;
        margin-left: 0.5rem;
    }

    .timeline-info {
        font-size: 0.8125rem;
        color: #6b7280;
        line-height: 1.6;
    }

    .timeline-info-row {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        margin-bottom: 0.375rem;
    }

    .timeline-info-row:last-child {
        margin-bottom: 0;
    }

    .timeline-info-icon {
        color: #9ca3af;
        font-size: 0.875rem;
        min-width: 1rem;
        margin-top: 0.125rem;
    }

    .timeline-info-text {
        flex: 1;
        color: #4b5563;
    }

    .timeline-empty {
        text-align: center;
        padding: 1.5rem;
        color: #9ca3af;
        font-size: 0.875rem;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes pulse {
        0%, 100% {
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }
        50% {
            box-shadow: 0 2px 16px rgba(59, 130, 246, 0.6);
        }
    }
</style>

<div class="status-timeline-container">
    @if($timelines && $timelines->count() > 0)
        <div class="timeline-vertical">
            @foreach($timelines as $timeline)
                @php
                    $statusClass = strtolower(str_replace(' ', '', $timeline->status));
                @endphp
                <div class="timeline-entry">
                    <div class="timeline-icon status-{{ $statusClass }} {{ $timeline->status === $currentStatus ? 'current' : '' }}">
                        <i class="fas fa-check"></i>
                    </div>
                    
                    <div class="timeline-content">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-status-badge :status="$timeline->status" />
                            @if($timeline->status === $currentStatus)
                                <span class="timeline-badge-current">Current</span>
                            @endif
                        </div>

                        <div class="timeline-info">
                            <div class="timeline-info-row">
                                <i class="far fa-clock timeline-info-icon"></i>
                                <span class="timeline-info-text">{{ $timeline->created_at->format('M d, Y g:i A') }}</span>
                            </div>

                            @if($timeline->assigned_personnel)
                                <div class="timeline-info-row">
                                    <i class="fas fa-user timeline-info-icon"></i>
                                    <span class="timeline-info-text">{{ $timeline->assigned_personnel }}</span>
                                </div>
                            @endif

                            @if($timeline->action_taken)
                                <div class="timeline-info-row">
                                    <i class="fas fa-tasks timeline-info-icon"></i>
                                    <span class="timeline-info-text">{{ $timeline->action_taken }}</span>
                                </div>
                            @endif

                            @if($timeline->remarks)
                                <div class="timeline-info-row">
                                    <i class="fas fa-comment timeline-info-icon"></i>
                                    <span class="timeline-info-text">{{ $timeline->remarks }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="timeline-empty">
            <i class="fas fa-history" style="font-size: 1.5rem; margin-bottom: 0.5rem; display: block;"></i>
            <div>No timeline history available yet.</div>
        </div>
    @endif
</div>
