@extends('layouts.dilg-app')

@section('title', 'Analytics & Reports - DILG-RC')

@section('content')
<?php
// Ensure all variables are set
$stats = $stats ?? [];
$reportsByBarangay = $reportsByBarangay ?? collect();
$reportsByViolationType = $reportsByViolationType ?? collect();
$reportsByStatus = $reportsByStatus ?? collect();
$monthlyTrend = $monthlyTrend ?? collect();
$resolvedVsPending = $resolvedVsPending ?? [];
$responseTimeByBarangay = $responseTimeByBarangay ?? collect();
?>

<!-- Chart.js Library -->
<script src="{{ asset('vendor/chart.js/chart.umd.js') }}"></script>
<script src="{{ asset('js/analytics-donuts.js') }}"></script>

<style>
    /* Modern Minimalist Analytics Design */
    .page-header-analytics {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .page-title-analytics {
        font-size: 1.875rem;
        font-weight: 700;
        color: #1f2937;
    }

    .user-badge {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #D4A017, #F4C542);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1rem;
    }

    /* Chart Container */
    .chart-container {
        position: relative;
        height: 420px;
        margin: 0.75rem auto 0;
        max-width: 540px;
        width: 100%;
    }

    .chart-description { margin-top: 0.3rem; color: #64748b; font-size: 0.78rem; }

    /* Two Column Chart Grid */
    .chart-grid-2col {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 768px) {
        .chart-grid-2col {
            grid-template-columns: 1fr;
        }
    }

    /* Pastel Metric Cards - Similar to the design */
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .metric-card {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }

    .metric-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        transform: translateY(-2px);
    }

    .metric-card.blue {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        border-color: #93c5fd;
    }

    .metric-card.purple {
        background: linear-gradient(135deg, #e9d5ff 0%, #d8b4fe 100%);
        border-color: #c084fc;
    }

    .metric-card.green {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        border-color: #6ee7b7;
    }

    .metric-card.pink {
        background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%);
        border-color: #f9a8d4;
    }

    .metric-card.orange {
        background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%);
        border-color: #fb923c;
    }

    .metric-card.yellow {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border-color: #fcd34d;
    }

    .metric-card.cyan {
        background: linear-gradient(135deg, #cffafe 0%, #a5f3fc 100%);
        border-color: #67e8f9;
    }

    .metric-card.indigo {
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        border-color: #a5b4fc;
    }

    .metric-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    .metric-card.blue .metric-icon { background: #3b82f6; color: white; }
    .metric-card.purple .metric-icon { background: #a855f7; color: white; }
    .metric-card.green .metric-icon { background: #10b981; color: white; }
    .metric-card.pink .metric-icon { background: #ec4899; color: white; }
    .metric-card.orange .metric-icon { background: #f97316; color: white; }
    .metric-card.yellow .metric-icon { background: #eab308; color: white; }
    .metric-card.cyan .metric-icon { background: #06b6d4; color: white; }
    .metric-card.indigo .metric-icon { background: #6366f1; color: white; }

    .metric-content {
        flex: 1;
    }

    .metric-label {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.25rem;
    }

    .metric-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1;
    }

    .metric-change {
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }

    .metric-change.positive {
        color: #10b981;
    }

    .metric-change.negative {
        color: #ef4444;
    }

    /* Section Cards */
    .section-card {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        margin-bottom: 1.5rem;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .section-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1e293b;
    }

    .section-filter {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        color: #64748b;
        padding: 0.5rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        background: #f8fafc;
        cursor: pointer;
        transition: all 0.2s;
    }

    .section-filter:hover {
        border-color: #cbd5e1;
        background: white;
    }

    /* Modern Bar Chart */
    .bar-chart {
        margin-top: 1rem;
    }

    .bar-item {
        margin-bottom: 1.25rem;
    }

    .bar-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
        color: #475569;
    }

    .bar-value {
        color: #1e293b;
        font-weight: 600;
    }

    .bar-track {
        height: 0.75rem;
        background: #f1f5f9;
        border-radius: 1rem;
        overflow: hidden;
        position: relative;
    }

    .bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%);
        transition: width 1s ease-out;
        border-radius: 1rem;
    }

    .bar-fill.blue { background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 100%); }
    .bar-fill.green { background: linear-gradient(90deg, #10b981 0%, #34d399 100%); }
    .bar-fill.orange { background: linear-gradient(90deg, #f97316 0%, #fb923c 100%); }
    .bar-fill.purple { background: linear-gradient(90deg, #a855f7 0%, #c084fc 100%); }

    /* Status Badges using DaisyUI */
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    /* Custom Status Badge Colors - Fallback */
    .badge.badge-info { background: #3b82f6; color: white; }
    .badge.badge-warning { background: #f59e0b; color: white; }
    .badge.badge-success { background: #10b981; color: white; }
    .badge.badge-primary { background: #6366f1; color: white; }
    .badge.badge-secondary { background: #a855f7; color: white; }
    .badge.badge-accent { background: #ec4899; color: white; }
    .badge.badge-error { background: #ef4444; color: white; }
    .badge.badge-neutral { background: #6b7280; color: white; }
    .badge.badge-ghost { background: #e5e7eb; color: #374151; }

    /* Table Styles */
    .table-responsive {
        overflow-x: auto;
        margin-top: 1rem;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        background: #f8fafc;
        padding: 0.875rem 1rem;
        text-align: left;
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        border-bottom: 1px solid #e2e8f0;
    }

    td {
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
        border-bottom: 1px solid #f1f5f9;
        color: #475569;
    }

    tbody tr:hover {
        background: #f8fafc;
    }

    /* Pill Stats */
    .pill-chart {
        display: flex;
        gap: 1.5rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }

    .pill-item {
        flex: 1;
        min-width: 180px;
        background: #f8fafc;
        padding: 1.5rem;
        border-radius: 0.75rem;
        text-align: center;
        border: 1px solid #e2e8f0;
        transition: all 0.3s;
    }

    .pill-item:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }

    .pill-value {
        font-size: 2rem;
        font-weight: 700;
        color: #1e293b;
    }

    .pill-label {
        font-size: 0.875rem;
        color: #64748b;
        margin-top: 0.5rem;
        font-weight: 500;
    }

    /* Export Buttons */
    .export-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .export-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        border: 1px solid #e2e8f0;
        cursor: pointer;
        transition: all 0.2s;
        background: white;
        color: #475569;
    }

    .export-btn:hover {
        border-color: #cbd5e1;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transform: translateY(-1px);
    }

    @media (max-width: 768px) {
        .metrics-grid {
            grid-template-columns: 1fr;
        }
        
        .page-header-analytics {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .chart-container { height: 380px; }
    }
</style>

<script>
function showExportPlaceholder(format) {
    alert(format + ' export will be implemented in Phase 17.');
}
</script>

<!-- Modern Page Header -->
<div class="page-header-analytics">
    <h1 class="page-title-analytics">Analytics & Reports</h1>
    <div class="user-badge">
        <div class="user-avatar">DA</div>
    </div>
</div>

<!-- Pastel Metric Cards -->
<div class="metrics-grid">
    <div class="metric-card blue">
        <div class="metric-icon">
            <i class="fas fa-file-alt"></i>
        </div>
        <div class="metric-content">
            <div class="metric-label">Total Reports</div>
            <div class="metric-value">{{ number_format($stats['total_reports']) }}</div>
        </div>
    </div>

    <div class="metric-card purple">
        <div class="metric-icon">
            <i class="fas fa-map-marked-alt"></i>
        </div>
        <div class="metric-content">
            <div class="metric-label">Barangays</div>
            <div class="metric-value">{{ $stats['total_barangays'] }}</div>
        </div>
    </div>

    <div class="metric-card green">
        <div class="metric-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="metric-content">
            <div class="metric-label">Resolved</div>
            <div class="metric-value">{{ number_format($stats['resolved']) }}</div>
        </div>
    </div>

    <div class="metric-card pink">
        <div class="metric-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="metric-content">
            <div class="metric-label">Avg Response</div>
            <div class="metric-value">{{ $stats['avg_response_time'] }}h</div>
        </div>
    </div>

    <div class="metric-card orange">
        <div class="metric-icon">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="metric-content">
            <div class="metric-label">Pending</div>
            <div class="metric-value">{{ number_format($stats['pending_verification']) }}</div>
        </div>
    </div>

    <div class="metric-card yellow">
        <div class="metric-icon">
            <i class="fas fa-certificate"></i>
        </div>
        <div class="metric-content">
            <div class="metric-label">Verified</div>
            <div class="metric-value">{{ number_format($stats['verified_violations']) }}</div>
        </div>
    </div>

    <div class="metric-card cyan">
        <div class="metric-icon">
            <i class="fas fa-tasks"></i>
        </div>
        <div class="metric-content">
            <div class="metric-label">In Progress</div>
            <div class="metric-value">{{ number_format($stats['in_progress']) }}</div>
        </div>
    </div>

    <div class="metric-card indigo">
        <div class="metric-icon">
            <i class="fas fa-times-circle"></i>
        </div>
        <div class="metric-content">
            <div class="metric-label">Rejected</div>
            <div class="metric-value">{{ number_format($stats['rejected']) }}</div>
        </div>
    </div>
</div>

<!-- Performance Overview -->
<div class="section-card">
    <div class="section-header">
        <h2 class="section-title">Performance Overview</h2>
        <div class="export-buttons">
            <a href="{{ route('analytics-reports.print') }}" target="_blank" class="export-btn">
                <i class="fas fa-print"></i> Print
            </a>
            <button onclick="showExportPlaceholder('CSV')" class="export-btn">
                <i class="fas fa-file-csv"></i> CSV
            </button>
            <button onclick="showExportPlaceholder('PDF')" class="export-btn">
                <i class="fas fa-file-pdf"></i> PDF
            </button>
        </div>
    </div>
    <div class="pill-chart">
        <div class="pill-item">
            <div class="pill-value">{{ number_format($resolvedVsPending['resolved']) }}</div>
            <div class="pill-label">Resolved Reports</div>
        </div>
        <div class="pill-item">
            <div class="pill-value">{{ number_format($resolvedVsPending['pending']) }}</div>
            <div class="pill-label">Pending Reports</div>
        </div>
        <div class="pill-item">
            <div class="pill-value">
                @php
                    $total = $resolvedVsPending['resolved'] + $resolvedVsPending['pending'];
                    $resolutionRate = $total > 0 ? round(($resolvedVsPending['resolved'] / $total) * 100, 1) : 0;
                @endphp
                {{ $resolutionRate }}%
            </div>
            <div class="pill-label">Resolution Rate</div>
        </div>
    </div>
</div>

<!-- Reports by Barangay & Violation Type - Side by Side -->
<div class="chart-grid-2col">
    <!-- Reports by Barangay -->
    <div class="section-card">
        <div class="section-header">
            <div><h2 class="section-title">Reports by Barangay</h2><p class="chart-description">Share of mapped reports across the ten busiest barangays</p></div>
            <div class="section-filter">
                <span>Last week</span>
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="barangayPieChart" role="img" aria-label="Doughnut chart showing report totals and percentages by barangay"></canvas>
        </div>
    </div>

    <!-- Reports by Violation Type -->
    <div class="section-card">
        <div class="section-header">
            <div><h2 class="section-title">Reports by Violation Type</h2><p class="chart-description">Distribution of reports by recorded obstruction category</p></div>
            <div class="section-filter">
                <span>Last week</span>
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="violationTypePieChart" role="img" aria-label="Doughnut chart showing report totals and percentages by violation type"></canvas>
        </div>
    </div>
</div>

<!-- Reports by Status -->
<div class="section-card">
    <div class="section-header">
        <h2 class="section-title">Reports by Status</h2>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                    <th>Percentage</th>
                    <th>Progress</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalStatusCount = $reportsByStatus->sum('count');
                @endphp
                @forelse($reportsByStatus as $item)
                    <tr>
                        <td><x-status-badge :status="$item->status" size="sm" /></td>
                        <td><strong>{{ $item->count }}</strong></td>
                        <td>{{ $totalStatusCount > 0 ? round(($item->count / $totalStatusCount) * 100, 1) : 0 }}%</td>
                        <td>
                            <div class="bar-track" style="height: 0.5rem; max-width: 150px;">
                                <div class="bar-fill green" style="width: {{ $totalStatusCount > 0 ? ($item->count / $totalStatusCount) * 100 : 0 }}%"></div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: #94a3b8;">No data available</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Monthly Trend -->
<div class="section-card">
    <div class="section-header">
        <h2 class="section-title">Monthly Report Trend</h2>
        <div class="section-filter">
            <span>Last 6 months</span>
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
    <div class="bar-chart">
        @php
            $maxMonthlyCount = $monthlyTrend->max('count') ?: 1;
        @endphp
        @forelse($monthlyTrend as $item)
            <div class="bar-item">
                <div class="bar-label">
                    <span>{{ $item->month_name }} {{ $item->year }}</span>
                    <span class="bar-value">{{ $item->count }} reports</span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill orange" style="width: {{ ($item->count / $maxMonthlyCount) * 100 }}%"></div>
                </div>
            </div>
        @empty
            <p style="text-align: center; color: #94a3b8;">No data for the last 6 months</p>
        @endforelse
    </div>
</div>

<!-- Response Time by Barangay -->
<div class="section-card">
    <div class="section-header">
        <h2 class="section-title">Response Time by Barangay</h2>
        <button class="export-btn">
            <span>Export</span>
            <i class="fas fa-download"></i>
        </button>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Barangay</th>
                    <th>Reports</th>
                    <th>Avg Response Time</th>
                    <th>Performance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($responseTimeByBarangay as $item)
                    <tr>
                        <td><strong>{{ $item->detected_barangay }}</strong></td>
                        <td>{{ $item->report_count }}</td>
                        <td>{{ $item->avg_response_time }} hours</td>
                        <td>
                            @if($item->avg_response_time < 24)
                                <span class="badge badge-success font-semibold text-xs">Excellent</span>
                            @elseif($item->avg_response_time < 48)
                                <span class="badge badge-info font-semibold text-xs">Good</span>
                            @elseif($item->avg_response_time < 72)
                                <span class="badge badge-warning font-semibold text-xs">Fair</span>
                            @else
                                <span class="badge badge-error font-semibold text-xs">Needs Improvement</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: #94a3b8;">No response time data available</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
// Barangay Doughnut Chart
const barangayData = {
    labels: @json($reportsByBarangay->take(10)->pluck('detected_barangay')->values()),
    datasets: [{
        data: @json($reportsByBarangay->take(10)->pluck('count')->map(fn ($count) => (int) $count)->values()),
        backgroundColor: [
            '#e9d5ff', // Light Purple
            '#6366f1', // Indigo
            '#06b6d4', // Cyan
            '#a7f3d0', // Light Green
            '#fde68a', // Light Yellow
            '#3b82f6', // Blue
            '#f97316', // Orange
            '#ec4899', // Pink
            '#84cc16', // Lime
            '#f43f5e', // Rose
        ],
        borderWidth: 0,
        spacing: 2
    }]
};

const barangayConfig = {
    type: 'doughnut',
    data: barangayData,
    options: {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '70%',
        plugins: {
            legend: {
                position: 'left',
                labels: {
                    boxWidth: 12,
                    boxHeight: 12,
                    padding: 10,
                    font: {
                        size: 11,
                        family: 'system-ui, -apple-system, sans-serif'
                    },
                    usePointStyle: true,
                    pointStyle: 'circle',
                    generateLabels: function(chart) {
                        const data = chart.data;
                        if (data.labels.length && data.datasets.length) {
                            const dataset = data.datasets[0];
                            const total = dataset.data.reduce((a, b) => a + b, 0);
                            return data.labels.map((label, i) => {
                                const value = dataset.data[i];
                                const percentage = ((value / total) * 100).toFixed(0);
                                return {
                                    text: `${label}  ${percentage}%`,
                                    fillStyle: dataset.backgroundColor[i],
                                    hidden: false,
                                    index: i
                                };
                            });
                        }
                        return [];
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += context.parsed + ' reports';
                        return label;
                    }
                }
            }
        }
    }
};

// Violation Type Doughnut Chart
const violationData = {
    labels: @json($reportsByViolationType->pluck('selected_violation_type')->values()),
    datasets: [{
        data: @json($reportsByViolationType->pluck('count')->map(fn ($count) => (int) $count)->values()),
        backgroundColor: [
            '#a855f7', // Purple
            '#ec4899', // Pink  
            '#06b6d4', // Cyan
            '#10b981', // Green
            '#f59e0b', // Amber
            '#3b82f6', // Blue
            '#ef4444', // Red
            '#6366f1', // Indigo
        ],
        borderWidth: 0,
        spacing: 2
    }]
};

const violationConfig = {
    type: 'doughnut',
    data: violationData,
    options: {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '70%',
        plugins: {
            legend: {
                position: 'left',
                labels: {
                    boxWidth: 12,
                    boxHeight: 12,
                    padding: 10,
                    font: {
                        size: 11,
                        family: 'system-ui, -apple-system, sans-serif'
                    },
                    usePointStyle: true,
                    pointStyle: 'circle',
                    generateLabels: function(chart) {
                        const data = chart.data;
                        if (data.labels.length && data.datasets.length) {
                            const dataset = data.datasets[0];
                            const total = dataset.data.reduce((a, b) => a + b, 0);
                            return data.labels.map((label, i) => {
                                const value = dataset.data[i];
                                const percentage = ((value / total) * 100).toFixed(0);
                                return {
                                    text: `${label}  ${percentage}%`,
                                    fillStyle: dataset.backgroundColor[i],
                                    hidden: false,
                                    index: i
                                };
                            });
                        }
                        return [];
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += context.parsed + ' reports';
                        return label;
                    }
                }
            }
        }
    }
};

const modernBarangayConfig = window.DilgAnalyticsDonut.createConfig({
    labels: barangayData.labels,
    values: barangayData.datasets[0].data,
    centerLabel: 'TOTAL REPORTS'
});

const modernViolationConfig = window.DilgAnalyticsDonut.createConfig({
    labels: violationData.labels,
    values: violationData.datasets[0].data,
    centerLabel: 'TOTAL REPORTS'
});

// Initialize charts when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Barangay Doughnut Chart
    const barangayCtx = document.getElementById('barangayPieChart');
    if (barangayCtx) {
        new Chart(barangayCtx, modernBarangayConfig);
    }

    // Violation Type Doughnut Chart
    const violationCtx = document.getElementById('violationTypePieChart');
    if (violationCtx) {
        new Chart(violationCtx, modernViolationConfig);
    }
});
</script>

@endsection
