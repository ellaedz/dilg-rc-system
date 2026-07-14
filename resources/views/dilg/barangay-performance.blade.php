@extends('layouts.dilg-app')

@section('title', 'Barangay Performance - DILG-RC')

@section('content')
<!-- Font Awesome Icons -->
<link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

<style>
    .page-header {
        background: linear-gradient(135deg, #F4C542 0%, #D4A017 100%);
        color: var(--dilg-dark-gray);
        padding: 2rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 12px rgba(212, 160, 23, 0.3);
    }

    .page-title {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }

    .page-subtitle {
        font-size: 1rem;
        opacity: 0.9;
    }

    .highlights-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .highlight-card {
        background: white;
        padding: 2rem;
        border-radius: 0.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        text-align: center;
        border-top: 4px solid;
    }

    .highlight-card.top { border-color: var(--dilg-success); }
    .highlight-card.attention { border-color: #f59e0b; }

    .highlight-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .highlight-title {
        font-size: 0.875rem;
        color: #6b7280;
        text-transform: uppercase;
        margin-bottom: 0.5rem;
    }

    .highlight-value {
        font-size: 2rem;
        font-weight: bold;
        color: var(--dilg-dark-gray);
        margin-bottom: 0.5rem;
    }

    .highlight-subtitle {
        font-size: 0.875rem;
        color: #6b7280;
    }

    .card {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .card-header {
        padding: 1.5rem;
        background: #f9fafb;
        border-bottom: 3px solid var(--dilg-yellow);
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--dilg-dark-gray);
    }

    .table-container {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        background: #f9fafb;
    }

    th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--dilg-dark-gray);
        text-transform: uppercase;
    }

    td {
        padding: 1rem;
        font-size: 0.875rem;
        border-bottom: 1px solid #e5e7eb;
    }

    tbody tr:hover {
        background: #fef3c7;
    }

    .rank-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        font-weight: bold;
        font-size: 1.125rem;
    }

    .rank-1 { background: #fbbf24; color: white; }
    .rank-2 { background: #d1d5db; color: var(--dilg-dark-gray); }
    .rank-3 { background: #d97706; color: white; }
    .rank-other { background: #e5e7eb; color: #6b7280; }

    .score-bar {
        width: 100%;
        height: 20px;
        background: #e5e7eb;
        border-radius: 10px;
        overflow: hidden;
    }

    .score-fill {
        height: 100%;
        border-radius: 10px;
        transition: width 0.3s;
    }

    .score-fill.excellent { background: var(--dilg-success); }
    .score-fill.good { background: #3b82f6; }
    .score-fill.average { background: #f59e0b; }
    .score-fill.poor { background: #ef4444; }

    .info-box {
        background: #dbeafe;
        border-left: 4px solid #3b82f6;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
        font-size: 0.875rem;
        color: #1e40af;
    }
</style>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-trophy"></i> Barangay Performance Rankings</h1>
    <p class="page-subtitle">Compare and track barangay performance in road clearing violation response - Santa Cruz, Laguna</p>
</div>

<!-- Top Performers Highlight -->
<div class="highlights-grid">
    @if($topPerformer)
    <div class="highlight-card top">
        <div class="highlight-icon"><i class="fas fa-medal"></i></div>
        <div class="highlight-title">Top Performing Barangay</div>
        <div class="highlight-value">{{ $topPerformer->detected_barangay }}</div>
        <div class="highlight-subtitle">Performance Score: {{ $topPerformer->performance_score }}</div>
    </div>
    @endif

    @if($needsAttention)
    <div class="highlight-card attention">
        <div class="highlight-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="highlight-title">Needs Attention</div>
        <div class="highlight-value">{{ $needsAttention->detected_barangay }}</div>
        <div class="highlight-subtitle">Performance Score: {{ $needsAttention->performance_score }}</div>
    </div>
    @endif
</div>

<!-- Performance Formula Info -->
<div class="info-box">
    <i class="fas fa-chart-line"></i> <strong>Performance Calculation:</strong> Score = (Resolution Rate × 50%) + (Response Time Score × 30%) + (Verification Completion × 20%)
    <br>Higher scores indicate better performance in addressing road clearing violations.
</div>

<!-- Performance Rankings Table -->
<div class="card">
    <div class="card-header"><i class="fas fa-chart-bar"></i> Barangay Performance Rankings</div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Barangay</th>
                    <th>Performance Score</th>
                    <th>Total Reports</th>
                    <th>Resolved</th>
                    <th>Pending</th>
                    <th>Resolution Rate</th>
                    <th>Avg Response Time</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($barangayPerformance as $performance)
                <tr>
                    <td>
                        <div class="rank-badge rank-{{ $performance->rank <= 3 ? $performance->rank : 'other' }}">
                            {{ $performance->rank }}
                        </div>
                    </td>
                    <td><strong>{{ $performance->detected_barangay }}</strong></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div class="score-bar" style="flex: 1;">
                                <div class="score-fill {{ $performance->performance_score >= 70 ? 'excellent' : ($performance->performance_score >= 50 ? 'good' : ($performance->performance_score >= 30 ? 'average' : 'poor')) }}" 
                                     style="width: {{ $performance->performance_score }}%;">
                                </div>
                            </div>
                            <span style="font-weight: 600; min-width: 50px;">{{ number_format($performance->performance_score, 1) }}</span>
                        </div>
                    </td>
                    <td>{{ $performance->total_reports }}</td>
                    <td style="color: var(--dilg-success); font-weight: 600;">{{ $performance->resolved_count }}</td>
                    <td style="color: #f59e0b; font-weight: 600;">{{ $performance->pending_count }}</td>
                    <td>
                        <span style="font-weight: 600; color: {{ $performance->resolution_rate >= 70 ? 'var(--dilg-success)' : ($performance->resolution_rate >= 40 ? '#f59e0b' : '#ef4444') }};">
                            {{ number_format($performance->resolution_rate, 1) }}%
                        </span>
                    </td>
                    <td>{{ $performance->avg_response_time }}</td>
                    <td>
                        <a href="{{ route('barangay.dashboard', $performance->detected_barangay) }}" 
                           style="color: #3b82f6; text-decoration: none; font-weight: 500;">
                            View Dashboard <i class="fas fa-arrow-right"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align: center; color: #6b7280; padding: 2rem;">
                        No performance data available yet. Data will appear as reports are processed.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Performance Insights -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
    <div class="card">
        <div style="padding: 1.5rem;">
            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem; color: var(--dilg-dark-gray);">
                <i class="fas fa-bullseye"></i> Performance Criteria
            </h3>
            <ul style="list-style: none; padding: 0;">
                <li style="padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb;">
                    <i class="fas fa-star" style="color: var(--dilg-success);"></i> <strong>Excellent:</strong> 70+ points
                </li>
                <li style="padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb;">
                    <i class="fas fa-thumbs-up" style="color: #3b82f6;"></i> <strong>Good:</strong> 50-69 points
                </li>
                <li style="padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb;">
                    <i class="fas fa-minus-circle" style="color: #f59e0b;"></i> <strong>Average:</strong> 30-49 points
                </li>
                <li style="padding: 0.5rem 0;">
                    <i class="fas fa-exclamation-circle" style="color: #ef4444;"></i> <strong>Needs Improvement:</strong> Below 30 points
                </li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div style="padding: 1.5rem;">
            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem; color: var(--dilg-dark-gray);">
                <i class="fas fa-chart-line"></i> Improvement Areas
            </h3>
            <ul style="list-style: none; padding: 0; color: #6b7280; font-size: 0.875rem;">
                <li style="padding: 0.5rem 0;"><i class="fas fa-check-circle" style="color: var(--dilg-success);"></i> Increase resolution rate</li>
                <li style="padding: 0.5rem 0;"><i class="fas fa-clock" style="color: #3b82f6;"></i> Reduce response time</li>
                <li style="padding: 0.5rem 0;"><i class="fas fa-clipboard-check" style="color: #f59e0b;"></i> Complete verifications faster</li>
                <li style="padding: 0.5rem 0;"><i class="fas fa-user-check" style="color: #8b5cf6;"></i> Assign personnel promptly</li>
            </ul>
        </div>
    </div>
</div>

@endsection
