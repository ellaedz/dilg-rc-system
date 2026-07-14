@extends('layouts.barangay-app')

@section('title', 'Barangay Dashboard - DILG-RC')

@section('content')
<section class="dashboard-hero" aria-labelledby="barangay-dashboard-title">
    <div class="dashboard-hero-copy">
        <div class="dashboard-eyebrow">Barangay operations</div>
        <h1 id="barangay-dashboard-title">{{ $barangay }}</h1>
        <p>Review incoming road clearing reports, coordinate action, and keep response records current.</p>
    </div>
    <div class="dashboard-hero-actions">
        <a href="{{ route('barangay.incoming-reports', $barangay) }}" class="btn bg-[#F4C542] hover:bg-[#f8d968] border-none text-slate-900">
            <i class="fas fa-inbox" aria-hidden="true"></i> Review incoming
            @if($stats['new_reports'] > 0)<span class="badge badge-neutral badge-sm" data-stat="new_reports">{{ $stats['new_reports'] }}</span>@endif
        </a>
        <a href="{{ route('barangay.response-tracking', $barangay) }}" class="btn border-white/25 bg-white/10 hover:bg-white/20 text-white">
            <i class="fas fa-chart-line" aria-hidden="true"></i> Track response
        </a>
    </div>
</section>

<section class="dashboard-metrics" aria-label="Barangay report statistics">
    @php
        $metrics = [
            ['key' => 'total_reports', 'label' => 'Total reports', 'value' => $stats['total_reports'], 'icon' => 'fa-file-lines', 'color' => '#2563eb', 'bg' => '#dbeafe'],
            ['key' => 'new_reports', 'label' => 'New submissions', 'value' => $stats['new_reports'], 'icon' => 'fa-inbox', 'color' => '#ea580c', 'bg' => '#ffedd5'],
            ['key' => 'verified_reports', 'label' => 'Verified', 'value' => $stats['verified_reports'], 'icon' => 'fa-circle-check', 'color' => '#0891b2', 'bg' => '#cffafe'],
            ['key' => 'in_progress', 'label' => 'In progress', 'value' => $stats['in_progress'], 'icon' => 'fa-person-digging', 'color' => '#7c3aed', 'bg' => '#ede9fe'],
            ['key' => 'resolved_reports', 'label' => 'Resolved', 'value' => $stats['resolved_reports'], 'icon' => 'fa-check-double', 'color' => '#059669', 'bg' => '#d1fae5'],
            ['key' => null, 'label' => 'Avg. response', 'value' => $stats['avg_response_time'], 'icon' => 'fa-stopwatch', 'color' => '#db2777', 'bg' => '#fce7f3'],
        ];
    @endphp
    @foreach($metrics as $metric)
        <article class="dashboard-metric" style="--metric-color: {{ $metric['color'] }}; --metric-bg: {{ $metric['bg'] }};">
            <div class="dashboard-metric-icon"><i class="fas {{ $metric['icon'] }}" aria-hidden="true"></i></div>
            <div class="dashboard-metric-label">{{ $metric['label'] }}</div>
            <div class="dashboard-metric-value text-balance" @if($metric['key']) data-stat="{{ $metric['key'] }}" @endif>
                {{ is_numeric($metric['value']) ? number_format($metric['value']) : $metric['value'] }}
            </div>
        </article>
    @endforeach
</section>

<div class="dashboard-grid">
    <section class="dashboard-panel" aria-labelledby="recent-reports-title">
        <div class="dashboard-panel-header">
            <div>
                <h2 id="recent-reports-title" class="dashboard-panel-title">Recent reports</h2>
                <p class="dashboard-panel-subtitle">Latest activity assigned to {{ $barangay }}</p>
            </div>
            <a href="{{ route('barangay.incoming-reports', $barangay) }}" class="btn btn-sm btn-ghost text-[#9a720d]">Open inbox <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        @if($recentReports->isEmpty())
            <div class="dashboard-empty"><i class="far fa-folder-open" aria-hidden="true"></i>No reports have been assigned to this barangay yet.</div>
        @else
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead><tr><th>Report</th><th>Violation</th><th>Status</th><th>Submitted</th><th><span class="sr-only">Action</span></th></tr></thead>
                    <tbody>
                        @foreach($recentReports as $report)
                            <tr>
                                <td><span class="font-extrabold text-slate-800">{{ $report->report_id }}</span></td>
                                <td><span class="block max-w-64 truncate" title="{{ $report->selected_violation_type }}">{{ $report->selected_violation_type }}</span></td>
                                <td><x-status-badge :status="$report->status" size="sm" /></td>
                                <td class="whitespace-nowrap text-sm text-slate-500">{{ $report->created_at->format('M d, Y') }}</td>
                                <td><a href="{{ route('violation-reports.show', $report) }}" class="btn btn-sm btn-ghost" aria-label="View report {{ $report->report_id }}"><i class="fas fa-chevron-right" aria-hidden="true"></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <aside class="dashboard-panel" aria-labelledby="workflow-title">
        <div class="dashboard-panel-header">
            <div>
                <h2 id="workflow-title" class="dashboard-panel-title">Report workflow</h2>
                <p class="dashboard-panel-subtitle">Continue work from the correct queue</p>
            </div>
        </div>
        <div class="dashboard-panel-body priority-list">
            <a href="{{ route('barangay.incoming-reports', $barangay) }}" class="priority-item hover:border-orange-300">
                <span class="priority-icon bg-orange-100 text-orange-700"><i class="fas fa-inbox" aria-hidden="true"></i></span>
                <span class="priority-copy"><strong>Verify incoming reports</strong><span>Confirm valid submissions first</span></span>
                <span class="priority-count" data-stat="new_reports">{{ number_format($stats['new_reports']) }}</span>
            </a>
            <a href="{{ route('barangay.verified-reports', $barangay) }}" class="priority-item hover:border-cyan-300">
                <span class="priority-icon bg-cyan-100 text-cyan-700"><i class="fas fa-user-check" aria-hidden="true"></i></span>
                <span class="priority-copy"><strong>Assign verified reports</strong><span>Send confirmed issues for action</span></span>
                <span class="priority-count" data-stat="verified_reports">{{ number_format($stats['verified_reports']) }}</span>
            </a>
            <a href="{{ route('barangay.response-tracking', $barangay) }}" class="priority-item hover:border-violet-300">
                <span class="priority-icon bg-violet-100 text-violet-700"><i class="fas fa-person-digging" aria-hidden="true"></i></span>
                <span class="priority-copy"><strong>Update field response</strong><span>Track assigned and active work</span></span>
                <span class="priority-count" data-stat="in_progress">{{ number_format($stats['in_progress']) }}</span>
            </a>
        </div>
    </aside>
</div>

<div class="dashboard-grid equal">
    <section class="dashboard-panel" aria-labelledby="violation-types-title">
        <div class="dashboard-panel-header">
            <div><h2 id="violation-types-title" class="dashboard-panel-title">Reports by violation type</h2><p class="dashboard-panel-subtitle">Most frequently reported obstructions</p></div>
        </div>
        <div class="dashboard-panel-body">
            @php $maxTypeCount = max(1, (int) $reportsByType->max('count')); @endphp
            @if($reportsByType->isEmpty())
                <div class="dashboard-empty py-7"><i class="far fa-chart-bar" aria-hidden="true"></i>No violation data available.</div>
            @else
                <div class="breakdown-list">
                    @foreach($reportsByType as $type)
                        <div>
                            <div class="breakdown-row-head"><span>{{ $type->selected_violation_type }}</span><strong>{{ number_format($type->count) }}</strong></div>
                            <div class="breakdown-track"><div class="breakdown-fill" style="width: {{ ($type->count / $maxTypeCount) * 100 }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="dashboard-panel" aria-labelledby="report-status-title">
        <div class="dashboard-panel-header">
            <div><h2 id="report-status-title" class="dashboard-panel-title">Reports by status</h2><p class="dashboard-panel-subtitle">Current distribution across the workflow</p></div>
        </div>
        <div class="dashboard-panel-body">
            @php $maxStatusCount = max(1, (int) $reportsByStatus->max('count')); @endphp
            @if($reportsByStatus->isEmpty())
                <div class="dashboard-empty py-7"><i class="far fa-chart-bar" aria-hidden="true"></i>No status data available.</div>
            @else
                <div class="breakdown-list">
                    @foreach($reportsByStatus as $status)
                        <div>
                            <div class="breakdown-row-head"><x-status-badge :status="$status->status" size="sm" /><strong>{{ number_format($status->count) }}</strong></div>
                            <div class="breakdown-track"><div class="breakdown-fill" style="width: {{ ($status->count / $maxStatusCount) * 100 }}%; background: linear-gradient(90deg, #0891b2, #22d3ee);"></div></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</div>

<script>
    (() => {
        const barangayName = @json($barangay);
        const updateBarangayStats = async () => {
            try {
                const response = await fetch(`/api/barangay/${encodeURIComponent(barangayName)}/dashboard-stats`, { headers: { Accept: 'application/json' } });
                if (!response.ok) return;
                const data = await response.json();
                Object.entries(data).forEach(([key, value]) => {
                    document.querySelectorAll(`[data-stat="${key}"]`).forEach(element => {
                        const formatted = Number(value).toLocaleString();
                        if (element.textContent.trim() !== formatted) element.textContent = formatted;
                    });
                });
            } catch (error) {
                console.warn('Barangay statistics could not be refreshed.', error);
            }
        };
        window.setInterval(updateBarangayStats, 10000);
    })();
</script>
@endsection
