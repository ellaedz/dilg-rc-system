@extends('layouts.dilg-app')

@section('title', 'DILG Admin Dashboard - DILG-RC')

@section('content')
<section class="dashboard-hero" aria-labelledby="dashboard-title">
    <div class="dashboard-hero-copy">
        <div class="dashboard-eyebrow">Municipal command center</div>
        <h1 id="dashboard-title">Road Clearing Overview</h1>
        <p>Monitor reports across Santa Cruz, prioritize routing issues, and track barangay response from one workspace.</p>
    </div>
    <div class="dashboard-hero-actions">
        <a href="{{ route('dilg.needs-barangay-review.index') }}" class="btn bg-[#F4C542] hover:bg-[#f8d968] border-none text-slate-900">
            <i class="fas fa-map-location-dot" aria-hidden="true"></i>
            Review routing
            @if($stats['needs_barangay_review'] > 0)
                <span class="badge badge-neutral badge-sm">{{ $stats['needs_barangay_review'] }}</span>
            @endif
        </a>
        <a href="{{ route('gis.index') }}" class="btn border-white/25 bg-white/10 hover:bg-white/20 text-white">
            <i class="fas fa-map" aria-hidden="true"></i> Open GIS map
        </a>
    </div>
</section>

<section class="dashboard-metrics" aria-label="Report statistics">
    @php
        $metrics = [
            ['key' => 'total_reports', 'label' => 'Total reports', 'value' => $stats['total_reports'], 'icon' => 'fa-file-lines', 'color' => '#2563eb', 'bg' => '#dbeafe'],
            ['key' => 'new_reports', 'label' => 'New submissions', 'value' => $stats['new_reports'], 'icon' => 'fa-inbox', 'color' => '#ea580c', 'bg' => '#ffedd5'],
            ['key' => 'verified_reports', 'label' => 'Verified', 'value' => $stats['verified_reports'], 'icon' => 'fa-circle-check', 'color' => '#0891b2', 'bg' => '#cffafe'],
            ['key' => 'pending_reports', 'label' => 'Pending action', 'value' => $stats['pending_reports'], 'icon' => 'fa-clock', 'color' => '#ca8a04', 'bg' => '#fef9c3'],
            ['key' => 'resolved_reports', 'label' => 'Resolved', 'value' => $stats['resolved_reports'], 'icon' => 'fa-check-double', 'color' => '#059669', 'bg' => '#d1fae5'],
            ['key' => null, 'label' => 'Barangays', 'value' => $stats['total_barangays'], 'icon' => 'fa-building-shield', 'color' => '#7c3aed', 'bg' => '#ede9fe'],
        ];
    @endphp
    @foreach($metrics as $metric)
        <article class="dashboard-metric" style="--metric-color: {{ $metric['color'] }}; --metric-bg: {{ $metric['bg'] }};">
            <div class="dashboard-metric-icon"><i class="fas {{ $metric['icon'] }}" aria-hidden="true"></i></div>
            <div class="dashboard-metric-label">{{ $metric['label'] }}</div>
            <div class="dashboard-metric-value" @if($metric['key']) data-stat="{{ $metric['key'] }}" @endif>{{ number_format($metric['value']) }}</div>
        </article>
    @endforeach
</section>

<div class="dashboard-grid">
    <section class="dashboard-panel" aria-labelledby="recent-reports-title">
        <div class="dashboard-panel-header">
            <div>
                <h2 id="recent-reports-title" class="dashboard-panel-title">Recent reports</h2>
                <p class="dashboard-panel-subtitle">Latest submissions received across all barangays</p>
            </div>
            <a href="{{ route('violation-reports.index') }}" class="btn btn-sm btn-ghost text-[#9a720d]">View all <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        @if($recentReports->isEmpty())
            <div class="dashboard-empty"><i class="far fa-folder-open" aria-hidden="true"></i>No reports have been submitted yet.</div>
        @else
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr><th>Report</th><th>Barangay</th><th>Violation</th><th>Status</th><th>Submitted</th><th><span class="sr-only">Action</span></th></tr>
                    </thead>
                    <tbody>
                        @foreach($recentReports as $report)
                            <tr>
                                <td><span class="font-extrabold text-slate-800">{{ $report->report_id }}</span></td>
                                <td>
                                    @if($report->effective_barangay)
                                        <span class="font-semibold text-slate-700">{{ $report->effective_barangay }}</span>
                                    @else
                                        <span class="badge badge-error badge-sm text-white">Needs review</span>
                                    @endif
                                </td>
                                <td><span class="block max-w-48 truncate" title="{{ $report->selected_violation_type }}">{{ $report->selected_violation_type }}</span></td>
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

    <aside class="dashboard-panel" aria-labelledby="priorities-title">
        <div class="dashboard-panel-header">
            <div>
                <h2 id="priorities-title" class="dashboard-panel-title">Operational priorities</h2>
                <p class="dashboard-panel-subtitle">Items requiring municipal attention</p>
            </div>
        </div>
        <div class="dashboard-panel-body priority-list">
            <a href="{{ route('dilg.needs-barangay-review.index') }}" class="priority-item hover:border-amber-300">
                <span class="priority-icon bg-amber-100 text-amber-700"><i class="fas fa-map-pin" aria-hidden="true"></i></span>
                <span class="priority-copy"><strong>Barangay routing review</strong><span>Reports awaiting a confirmed assignment</span></span>
                <span class="priority-count" data-stat="needs_barangay_review">{{ number_format($stats['needs_barangay_review']) }}</span>
            </a>
            <a href="{{ route('violation-reports.index', ['status' => 'Submitted']) }}" class="priority-item hover:border-orange-300">
                <span class="priority-icon bg-orange-100 text-orange-700"><i class="fas fa-inbox" aria-hidden="true"></i></span>
                <span class="priority-copy"><strong>New submissions</strong><span>Recently received and unprocessed</span></span>
                <span class="priority-count" data-stat="new_reports">{{ number_format($stats['new_reports']) }}</span>
            </a>
            <div class="priority-item">
                <span class="priority-icon bg-blue-100 text-blue-700"><i class="fas fa-route" aria-hidden="true"></i></span>
                <span class="priority-copy"><strong>Manually routed</strong><span>Reports assigned by DILG staff</span></span>
                <span class="priority-count" data-stat="manually_routed">{{ number_format($stats['manually_routed']) }}</span>
            </div>
        </div>
    </aside>
</div>

<div class="dashboard-grid equal">
    <article class="performance-card border-l-4 border-l-emerald-500">
        <div class="performance-label"><i class="fas fa-trophy mr-2 text-emerald-600" aria-hidden="true"></i>Top resolution performance</div>
        <div class="performance-name">{{ $stats['top_barangay'] }}</div>
        <div class="performance-value text-emerald-700">{{ $stats['top_barangay_resolution_rate'] }} resolution rate</div>
    </article>
    <article class="performance-card border-l-4 border-l-orange-500">
        <div class="performance-label"><i class="fas fa-triangle-exclamation mr-2 text-orange-600" aria-hidden="true"></i>Highest pending workload</div>
        <div class="performance-name">{{ $stats['most_pending_barangay'] }}</div>
        <div class="performance-value text-orange-700">{{ number_format($stats['most_pending_count']) }} pending {{ Str::plural('report', $stats['most_pending_count']) }}</div>
    </article>
</div>

<section class="dashboard-panel mb-5" aria-labelledby="barangay-summary-title">
    <div class="dashboard-panel-header">
        <div>
            <h2 id="barangay-summary-title" class="dashboard-panel-title">Barangay performance summary</h2>
            <p class="dashboard-panel-subtitle">Workload and resolution progress by barangay</p>
        </div>
        <a href="{{ route('barangay-performance.index') }}" class="btn btn-sm btn-ghost text-[#9a720d]">Full performance</a>
    </div>
    @if($reportsByBarangay->isEmpty())
        <div class="dashboard-empty"><i class="far fa-chart-bar" aria-hidden="true"></i>No barangay report data is available.</div>
    @else
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead><tr><th>Barangay</th><th>Total</th><th>Resolved</th><th>Pending</th><th>Resolution rate</th><th><span class="sr-only">Action</span></th></tr></thead>
                <tbody>
                    @foreach($reportsByBarangay as $barangayRow)
                        @php $rate = $barangayRow->total_reports > 0 ? round(($barangayRow->resolved_count / $barangayRow->total_reports) * 100, 1) : 0; @endphp
                        <tr>
                            <td class="font-bold text-slate-800">{{ $barangayRow->detected_barangay }}</td>
                            <td>{{ number_format($barangayRow->total_reports) }}</td>
                            <td class="font-semibold text-emerald-700">{{ number_format($barangayRow->resolved_count) }}</td>
                            <td class="font-semibold text-orange-700">{{ number_format($barangayRow->pending_count) }}</td>
                            <td class="min-w-44">
                                <div class="flex items-center gap-3"><progress class="progress progress-success w-28" value="{{ $rate }}" max="100"></progress><strong class="text-sm">{{ $rate }}%</strong></div>
                            </td>
                            <td><a href="{{ route('barangay.dashboard', $barangayRow->detected_barangay) }}" class="btn btn-sm btn-ghost" aria-label="View {{ $barangayRow->detected_barangay }} dashboard"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

<script>
    (() => {
        const updateDashboardStats = async () => {
            try {
                const response = await fetch('/api/dilg-dashboard-stats', { headers: { Accept: 'application/json' } });
                if (!response.ok) return;
                const data = await response.json();
                Object.entries(data).forEach(([key, value]) => {
                    document.querySelectorAll(`[data-stat="${key}"]`).forEach(element => {
                        const formatted = Number(value).toLocaleString();
                        if (element.textContent.trim() !== formatted) element.textContent = formatted;
                    });
                });
            } catch (error) {
                console.warn('Dashboard statistics could not be refreshed.', error);
            }
        };
        window.setInterval(updateDashboardStats, 10000);
    })();
</script>
@endsection
