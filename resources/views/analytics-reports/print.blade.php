<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DILG-RC Summary Report - {{ now()->format('Y-m-d') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.8;
            color: #000000;
            padding: 0;
            background: white;
        }

+        .gov-status { display:inline-flex;align-items:center;gap:4px;padding:2px 7px;border:1px solid var(--ring,#cbd5e1);border-radius:999px;background:var(--bg,#eef2f6);color:var(--fg,#475569);font:700 9px Arial,sans-serif;white-space:nowrap;-webkit-print-color-adjust:exact;print-color-adjust:exact; }
        .gov-status-dot { width:5px;height:5px;border-radius:50%;background:currentColor; }
        .gov-status--submitted {--bg:#e8f1ff;--fg:#1558a6;--ring:#b8d4f7}.gov-status--for-verification {--bg:#fff4d6;--fg:#8a5a00;--ring:#efd28a}.gov-status--verified {--bg:#dff7f8;--fg:#0b6870;--ring:#9edfe3}.gov-status--assigned {--bg:#e8eafd;--fg:#3e46a3;--ring:#c4c8f5}.gov-status--in-progress {--bg:#f0e8ff;--fg:#6e34a7;--ring:#d6bdf3}.gov-status--action-taken {--bg:#e0f6ee;--fg:#08745a;--ring:#a7dfce}.gov-status--resolved {--bg:#e2f6e8;--fg:#176b35;--ring:#a9ddb9}.gov-status--rejected {--bg:#fde8e8;--fg:#a42525;--ring:#f1b8b8}.gov-status--closed {--bg:#e9edf2;--fg:#43536a;--ring:#c8d0da}

        .report-container {
            max-width: 8.5in;
            margin: 0 auto;
            padding: 1in;
            background: white;
        }

        .official-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 3px double #000000;
        }

        .header-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            margin-bottom: 1rem;
        }

        .dilg-logo {
            width: 100px;
            height: 100px;
            object-fit: contain;
        }

        .header-text {
            line-height: 1.4;
        }

        .republic-text {
            font-size: 0.875rem;
            font-weight: normal;
            margin: 0;
        }

        .department-text {
            font-size: 1rem;
            font-weight: bold;
            margin: 0.25rem 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .office-text {
            font-size: 0.875rem;
            font-weight: normal;
            margin: 0.25rem 0;
        }

        .location-text {
            font-size: 0.875rem;
            font-weight: normal;
            font-style: italic;
            margin-top: 0.5rem;
        }

        .report-header {
            text-align: center;
            margin: 2rem 0;
        }

        .report-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 0.5rem;
        }

        .report-subtitle {
            font-size: 1rem;
            color: #000000;
            margin-bottom: 0.25rem;
        }

        .report-meta {
            font-size: 0.875rem;
            color: #333333;
            margin-top: 1rem;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
            margin-bottom: 2rem;
            border: 1px solid #000000;
        }

        .summary-card {
            padding: 0.75rem;
            background: #ffffff;
            border-right: 1px solid #cccccc;
            border-bottom: 1px solid #cccccc;
        }

        .summary-card:nth-child(4n) {
            border-right: none;
        }

        .summary-label {
            font-size: 0.75rem;
            color: #000000;
            margin-bottom: 0.25rem;
            font-weight: normal;
        }

        .summary-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #000000;
        }

        .section {
            margin-bottom: 2rem;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: #000000;
            margin-bottom: 0.75rem;
            padding-bottom: 0.25rem;
            border-bottom: 2px solid #000000;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
            border: 1px solid #000000;
        }

        table thead {
            background: #f0f0f0;
        }

        table th {
            padding: 0.5rem;
            text-align: left;
            font-weight: 700;
            font-size: 0.75rem;
            color: #000000;
            border: 1px solid #000000;
            text-transform: uppercase;
        }

        table td {
            padding: 0.5rem;
            font-size: 0.8125rem;
            border: 1px solid #000000;
        }

        .highlight-card {
            padding: 1rem;
            background: #f5f5f5;
            border: 2px solid #000000;
            margin-bottom: 1rem;
            page-break-inside: avoid;
        }

        .highlight-title {
            font-weight: 700;
            font-size: 0.875rem;
            color: #000000;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }

        .highlight-value {
            font-size: 1.125rem;
            font-weight: 700;
            color: #000000;
        }

        .report-footer {
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 2px solid #000000;
            page-break-inside: avoid;
        }

        .signature-section {
            margin-top: 2rem;
            margin-bottom: 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .signature-box {
            text-align: center;
        }

        .signature-label {
            font-size: 0.75rem;
            color: #000000;
            margin-bottom: 0.5rem;
            font-weight: normal;
        }

        .signature-line {
            margin-top: 3rem;
            padding-top: 0.25rem;
            border-top: 1px solid #000000;
        }

        .signature-name {
            font-weight: 700;
            margin-top: 0.25rem;
            font-size: 0.875rem;
        }

        .signature-position {
            font-size: 0.75rem;
            font-style: italic;
            margin-top: 0.25rem;
        }

        .system-note {
            font-size: 0.6875rem;
            color: #333333;
            font-style: italic;
            margin-top: 1rem;
            text-align: justify;
        }

        .action-buttons {
            position: fixed;
            top: 1rem;
            right: 1rem;
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: 0.375rem;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #F4C542;
            color: #2C3E50;
        }

        .btn-primary:hover {
            background: #D4A017;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        @media print {
            .action-buttons {
                display: none;
            }

            body {
                padding: 0;
            }

            .report-container {
                max-width: 100%;
                padding: 0.5in;
            }

            .summary-grid {
                page-break-inside: avoid;
            }

            .section {
                page-break-inside: avoid;
            }

            table {
                page-break-inside: auto;
            }

            table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-footer-group;
            }
        }

        @media (max-width: 768px) {
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .action-buttons {
                position: static;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="action-buttons">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print Report
        </button>
        <a href="{{ route('analytics-reports.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="report-container">
        <!-- Official Government Header -->
        <div class="official-header">
            <div class="header-logos">
                <img src="{{ asset('images/dilg-logo.png') }}" alt="DILG Logo" class="dilg-logo">
            </div>
            <div class="header-text">
                <p class="republic-text">Republic of the Philippines</p>
                <p class="department-text">Department of the Interior and Local Government</p>
                <p class="office-text">Road Clearing Operations</p>
                <p class="location-text">Santa Cruz, Laguna</p>
            </div>
        </div>

        <!-- Report Title -->
        <div class="report-header">
            <h1 class="report-title">DILG-Wide Summary Report</h1>
            <p class="report-subtitle">Road Clearing Violation Monitoring System</p>
            <p class="report-meta">Report Generated: {{ now()->format('F d, Y \a\t g:i A') }}</p>
        </div>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-label">Total Reports</div>
                <div class="summary-value">{{ number_format($stats['total_reports']) }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Barangays Monitored</div>
                <div class="summary-value">{{ $stats['total_barangays'] }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Pending Verification</div>
                <div class="summary-value">{{ number_format($stats['pending_verification']) }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Verified Reports</div>
                <div class="summary-value">{{ number_format($stats['verified_violations']) }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">In Progress</div>
                <div class="summary-value">{{ number_format($stats['in_progress']) }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Action Taken</div>
                <div class="summary-value">{{ number_format($stats['action_taken']) }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Resolved</div>
                <div class="summary-value">{{ number_format($stats['resolved']) }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Avg Response Time</div>
                <div class="summary-value">{{ $stats['avg_response_time'] }}h</div>
            </div>
        </div>

        <!-- Violation Type Summary -->
        <div class="section">
            <h3 class="section-title">Violation Type Summary</h3>
            <table>
                <thead>
                    <tr>
                        <th>Violation Type</th>
                        <th style="text-align: right;">Count</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportsByViolationType as $item)
                        <tr>
                            <td>{{ $item->selected_violation_type }}</td>
                            <td style="text-align: right; font-weight: 600;">{{ number_format($item->count) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Barangay Summary Table -->
        <div class="section">
            <h3 class="section-title">Barangay Summary</h3>
            <table>
                <thead>
                    <tr>
                        <th>Barangay</th>
                        <th style="text-align: center;">Total</th>
                        <th style="text-align: center;">Verified</th>
                        <th style="text-align: center;">In Progress</th>
                        <th style="text-align: center;">Resolved</th>
                        <th style="text-align: center;">Pending</th>
                        <th style="text-align: right;">Avg Response Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($barangaySummary as $item)
                        <tr>
                            <td style="font-weight: 600;">{{ $item->detected_barangay }}</td>
                            <td style="text-align: center;">{{ number_format($item->total) }}</td>
                            <td style="text-align: center;">{{ number_format($item->verified) }}</td>
                            <td style="text-align: center;">{{ number_format($item->in_progress) }}</td>
                            <td style="text-align: center;">{{ number_format($item->resolved) }}</td>
                            <td style="text-align: center;">{{ number_format($item->pending) }}</td>
                            <td style="text-align: right;">{{ $item->avg_response_time }}h</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Status Summary -->
        <div class="section">
            <h3 class="section-title">Status Summary</h3>
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th style="text-align: right;">Count</th>
                        <th style="text-align: right;">Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($statusSummary as $item)
                        <tr>
                            <td><x-status-badge :status="$item->status" size="sm" /></td>
                            <td style="text-align: right; font-weight: 600;">{{ number_format($item->count) }}</td>
                            <td style="text-align: right;">{{ $item->percentage }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Top Performing Barangay -->
        @if($topPerformingBarangay)
        <div class="highlight-card">
            <div class="highlight-title">Top Performing Barangay</div>
            <div class="highlight-value">{{ $topPerformingBarangay->detected_barangay }}</div>
            <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #000000;">
                Performance Score: {{ $topPerformingBarangay->performance_score }}% | 
                Resolution Rate: {{ $topPerformingBarangay->resolution_rate }}% | 
                Average Response Time: {{ $topPerformingBarangay->avg_response_time }} hours
            </div>
        </div>
        @endif

        <!-- Barangay Needing Attention -->
        @if($barangayNeedingAttention)
        <div class="highlight-card" style="background: #ffffff; border: 2px solid #000000;">
            <div class="highlight-title">Barangay Needing Attention</div>
            <div class="highlight-value">{{ $barangayNeedingAttention->detected_barangay }}</div>
            <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #000000;">
                Pending Reports: {{ $barangayNeedingAttention->pending }} | 
                Total Reports: {{ $barangayNeedingAttention->total }}
            </div>
        </div>
        @endif

        <!-- Footer -->
        <div class="report-footer">
            <div class="signature-section">
                <div class="signature-box">
                    <p class="signature-label">Prepared by:</p>
                    <div class="signature-line">
                        <p class="signature-name">_________________________</p>
                        <p class="signature-position">DILG Administrator</p>
                    </div>
                </div>
                <div class="signature-box">
                    <p class="signature-label">Noted by:</p>
                    <div class="signature-line">
                        <p class="signature-name">_________________________</p>
                        <p class="signature-position">Regional Director</p>
                    </div>
                </div>
            </div>

            <p style="font-size: 0.8125rem; margin-bottom: 0.5rem;"><strong>Date Printed:</strong> {{ now()->format('F d, Y \a\t g:i A') }}</p>

            <p class="system-note">
                This report was automatically generated by the DILG Road Clearing Violation Monitoring System (DILG-RC) 
                based on submitted road-clearing violation reports from all 26 barangays in Santa Cruz, Laguna. 
                All data presented herein are derived from verified reports and official records maintained by the system.
            </p>
        </div>
    </div>
</body>
</html>
