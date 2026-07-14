@extends('layouts.app')

@section('title', 'Reports - DILG-RC System')

@section('content')
<style>
    .page-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .filter-group {
        display: flex;
        gap: 0.75rem;
        align-items: center;
    }

    .filter-select {
        padding: 0.75rem 1rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        background: white;
    }

    .filter-select:focus {
        outline: none;
        border-color: #3b82f6;
    }

    .location-badge {
        background: #eff6ff;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        color: #1e40af;
        font-weight: 600;
    }

    .table-container {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        background: #f9fafb;
    }

    th {
        padding: 1rem 1.5rem;
        text-align: left;
        font-size: 0.75rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    td {
        padding: 1rem 1.5rem;
        border-top: 1px solid #e5e7eb;
        color: #374151;
    }

    tbody tr:hover {
        background: #f9fafb;
    }

    .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-completed {
        background: #d1fae5;
        color: #065f46;
    }

    .badge-in-progress {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .btn-action {
        padding: 0.375rem 0.75rem;
        margin-right: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        background: white;
        color: #374151;
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-action:hover {
        background: #f9fafb;
        border-color: #3b82f6;
        color: #3b82f6;
    }

    .btn-download {
        background: #10b981;
        color: white;
        border: none;
    }

    .btn-download:hover {
        background: #059669;
        color: white;
    }
</style>

<div class="page-header">
    <h1 class="page-title">Reports</h1>
    <p class="page-subtitle">Generate and view system reports for Santa Cruz, Laguna</p>
</div>

<div class="page-actions">
    <button class="btn-primary">📊 Generate New Report</button>
    <div class="filter-group">
        <div class="location-badge">
            📍 Santa Cruz, Laguna
        </div>
        <select class="filter-select">
            <option>All Report Types</option>
            <option>Performance Report</option>
            <option>Development Report</option>
            <option>Complaints Report</option>
            <option>Financial Report</option>
            <option>Project Report</option>
        </select>
        <select class="filter-select">
            <option>All Barangays</option>
            <optgroup label="Poblacion">
                <option>Poblacion I</option>
                <option>Poblacion II</option>
                <option>Poblacion III</option>
                <option>Poblacion IV</option>
                <option>Poblacion V</option>
            </optgroup>
            <optgroup label="Rural Barangays">
                <option>Alipit</option>
                <option>Bagumbayan</option>
                <option>Bubukal</option>
                <option>Calios</option>
                <option>Duhat</option>
                <option>Gatid</option>
                <option>Jasaan</option>
                <option>Labuin</option>
                <option>Malinao</option>
                <option>Oogong</option>
                <option>Pagsawitan</option>
                <option>Palasan</option>
                <option>Patimbao</option>
                <option>San Jose</option>
                <option>San Juan</option>
                <option>San Pablo Norte</option>
                <option>San Pablo Sur</option>
                <option>Santisima Cruz</option>
                <option>Santo Angel Central</option>
                <option>Santo Angel Norte</option>
                <option>Santo Angel Sur</option>
            </optgroup>
        </select>
    </div>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Report ID</th>
                <th>Title</th>
                <th>Type</th>
                <th>Barangay</th>
                <th>Generated By</th>
                <th>Status</th>
                <th>Date Generated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reports as $report)
            <tr>
                <td><strong>{{ $report['id'] }}</strong></td>
                <td>{{ $report['title'] }}</td>
                <td>{{ $report['type'] }}</td>
                <td>{{ $report['barangay'] }}</td>
                <td>{{ $report['generated_by'] }}</td>
                <td>
                    <span class="badge badge-{{ strtolower(str_replace(' ', '-', $report['status'])) }}">
                        {{ $report['status'] }}
                    </span>
                </td>
                <td>{{ $report['date_generated'] }}</td>
                <td>
                    <button class="btn-action">👁️ View</button>
                    <button class="btn-action btn-download">📥 Download</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
