@extends('layouts.barangay-app')

@section('title', 'Analytics & Reports - ' . $barangay)

@section('content')
<?php
// Ensure all variables are set
$stats = $stats ?? [];
$reportsByStatus = $reportsByStatus ?? collect();
$reportsByViolationType = $reportsByViolationType ?? collect();
$monthlyTrend = $monthlyTrend ?? collect();
$recentReports = $recentReports ?? collect();
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

    /* Pastel Metric Cards */
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

    .metric-card.blue { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-color: #93c5fd; }
    .metric-card.purple { background: linear-gradient(135deg, #e9d5ff 0%, #d8b4fe 100%); border-color: #c084fc; }
    .metric-card.green { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-color: #6ee7b7; }
    .metric-card.pink { background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%); border-color: #f9a8d4; }
    .metric-card.orange { background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%); border-color: #fb923c; }
    .metric-card.cyan { background: linear-gradient(135deg, #cffafe 0%, #a5f3fc 100%); border-color: #67e8f9; }

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
    .metric-card.cyan .metric-icon { background: #06b6d4; color: white; }

    .metric-content { flex: 1; }

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

    /* Modern Bar Chart */
    .bar-chart { margin-top: 1rem; }
    .bar-item { margin-bottom: 1.25rem; }

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

    .bar-fill.green { background: linear-gradient(90deg, #10b981 0%, #34d399 100%); }
    .bar-fill.orange { background: linear-gradient(90deg, #f97316 0%, #fb923c 100%); }

    /* Table Styles */
    .table-responsive { overflow-x: auto; margin-top: 1rem; }
    table { width: 100%; border-collapse: collapse; }

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

    tbody tr:hover { background: #f8fafc; }

    /* Export Buttons */
    .export-buttons { display: flex; gap: 0.5rem; flex-wrap: wrap; }

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
        .chart-grid-2col { grid-template-columns: 1fr; }
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
    <div>
        <h1 class="page-title-analytics">Analytics & Reports</h1>
        <p class="text-gray-600 text-sm">Barangay {{ $barangay }} - Road Clearing Monitoring</p>
    </div>
</div>

<!-- Pastel Metric Cards -->
<div class="metrics-grid">
    <div class="metric-card blue">
        <div class="metric-icon"><i class="fas fa-file-alt"></i></div>
        <div class="metric-content">
            <div class="metric-label">Total Reports</div>
            <div class="metric-value">{{ number_format($stats['total_reports'] ?? 0) }}</div>
        </div>
    </div>

    <div class="metric-card orange">
        <div class="metric-icon"><i class="fas fa-inbox"></i></div>
        <div class="metric-content">
            <div class="metric-label">New Submissions</div>
            <div class="metric-value">{{ number_format($stats['new_reports'] ?? 0) }}</div>
        </div>
    </div>

    <div class="metric-card green">
        <div class="metric-icon"><i class="fas fa-check-circle"></i></div>
        <div class="metric-content">
            <div class="metric-label">Verified</div>
            <div class="metric-value">{{ number_format($stats['verified_reports'] ?? 0) }}</div>
        </div>
    </div>

    <div class="metric-card purple">
        <div class="metric-icon"><i class="fas fa-spinner"></i></div>
        <div class="metric-content">
            <div class="metric-label">In Progress</div>
            <div class="metric-value">{{ number_format($stats['in_progress'] ?? 0) }}</div>
        </div>
    </div>

    <div class="metric-card cyan">
        <div class="metric-icon"><i class="fas fa-check-double"></i></div>
        <div class="metric-content">
            <div class="metric-label">Resolved</div>
            <div class="metric-value">{{ number_format($stats['resolved_reports'] ?? 0) }}</div>
        </div>
    </div>

    <div class="metric-card pink">
        <div class="metric-icon"><i class="fas fa-clock"></i></div>
        <div class="metric-content">
            <div class="metric-label">Avg Response</div>
            <div class="metric-value">{{ $stats['avg_response_time'] ?? '0h' }}</div>
        </div>
    </div>
</div>

<!-- Performance Overview -->
<div class="section-card">
    <div class="section-header">
        <h2 class="section-title">Export Reports</h2>
        <div class="export-buttons">
            <a href="{{ route('barangay.analytics-reports.print', $barangay) }}" target="_blank" class="export-btn">
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
</div>

<!-- Reports by Violation Type & Status - Side by Side -->
<div class="chart-grid-2col">
    <!-- Reports by Violation Type -->
    <div class="section-card">
        <h2 class="section-title">Reports by Violation Type</h2>
        <p class="chart-description">Distribution of reports by recorded obstruction category</p>
        <div class="chart-container">
            <canvas id="violationTypePieChart" role="img" aria-label="Doughnut chart showing report totals and percentages by violation type"></canvas>
        </div>
    </div>

    <!-- Reports by Status -->
    <div class="section-card">
        <h2 class="section-title">Reports by Status</h2>
        <p class="chart-description">Current report distribution across the response workflow</p>
        <div class="chart-container">
            <canvas id="statusPieChart" role="img" aria-label="Doughnut chart showing report totals and percentages by status"></canvas>
        </div>
    </div>
</div>

<!-- Monthly Trend -->
<div class="section-card">
    <h2 class="section-title">Monthly Report Trend</h2>
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

<!-- Recent Reports Table -->
<div class="section-card">
    <h2 class="section-title">Recent Reports</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Report ID</th>
                    <th>Violation Type</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentReports as $report)
                    <tr>
                        <td><strong>{{ $report->report_id }}</strong></td>
                        <td>{{ $report->selected_violation_type }}</td>
                        <td><x-status-badge :status="$report->status" size="sm" /></td>
                        <td>{{ $report->created_at->format('M d, Y') }}</td>
                        <td>
                            <a href="{{ route('violation-reports.show', $report) }}" class="btn btn-xs btn-ghost text-blue-600">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: #94a3b8;">No recent reports</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
// Violation Type Doughnut Chart
const violationData = {
    labels: @json($reportsByViolationType->pluck('selected_violation_type')->values()),
    datasets: [{
        data: @json($reportsByViolationType->pluck('count')->map(fn ($count) => (int) $count)->values()),
        backgroundColor: ['#a855f7', '#ec4899', '#06b6d4', '#10b981', '#f59e0b', '#3b82f6', '#ef4444', '#6366f1'],
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
                    font: { size: 11 },
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
            }
        }
    }
};

// Status Doughnut Chart
const statusData = {
    labels: @json($reportsByStatus->pluck('status')->values()),
    datasets: [{
        data: @json($reportsByStatus->pluck('count')->map(fn ($count) => (int) $count)->values()),
        backgroundColor: ['#3b82f6', '#f59e0b', '#10b981', '#a855f7', '#ec4899', '#06b6d4', '#ef4444', '#6b7280'],
        borderWidth: 0,
        spacing: 2
    }]
};

const statusConfig = {
    type: 'doughnut',
    data: statusData,
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
                    font: { size: 11 },
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
            }
        }
    }
};

const modernViolationConfig = window.DilgAnalyticsDonut.createConfig({
    labels: violationData.labels,
    values: violationData.datasets[0].data,
    centerLabel: 'TOTAL REPORTS'
});

const modernStatusConfig = window.DilgAnalyticsDonut.createConfig({
    labels: statusData.labels,
    values: statusData.datasets[0].data,
    centerLabel: 'TOTAL REPORTS',
    colors: window.DilgAnalyticsDonut.statusColors(statusData.labels)
});

// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    const violationCtx = document.getElementById('violationTypePieChart');
    if (violationCtx) new Chart(violationCtx, modernViolationConfig);

    const statusCtx = document.getElementById('statusPieChart');
    if (statusCtx) new Chart(statusCtx, modernStatusConfig);
});
</script>

@endsection
