@extends('layouts.app')

@section('title', 'Verified Reports - DILG-RC')

@section('content')
<style>
    .filter-bar {
        background: white;
        padding: 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #4b5563;
        margin-bottom: 0.5rem;
    }

    .filter-select {
        padding: 0.625rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.875rem;
    }

    .btn-filter {
        background: var(--dilg-yellow);
        color: var(--dilg-dark-gray);
        padding: 0.625rem 1.5rem;
        border: none;
        border-radius: 0.375rem;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s;
        margin-top: 0.5rem;
    }

    .btn-filter:hover {
        background: var(--dilg-dark-gold);
        color: white;
    }

    .card {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        overflow: hidden;
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
        border-bottom: 2px solid var(--dilg-yellow);
    }

    th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--dilg-dark-gray);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    td {
        padding: 1rem;
        font-size: 0.875rem;
        border-bottom: 1px solid #e5e7eb;
    }

    tbody tr:hover {
        background: #d1fae5;
    }

    .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .badge-verified { background: #d1fae5; color: #065f46; }
    .badge-assigned { background: #e0e7ff; color: #3730a3; }
    .badge-inprogress { background: #fce7f3; color: #9f1239; }
    .badge-actiontaken { background: #ddd6fe; color: #5b21b6; }

    .btn-action {
        padding: 0.375rem 0.75rem;
        margin: 0 0.25rem;
        border: none;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: all 0.2s;
    }

    .btn-view {
        background: #3b82f6;
        color: white;
    }

    .btn-view:hover {
        background: #2563eb;
    }

    .btn-assign {
        background: var(--dilg-success);
        color: white;
    }

    .btn-assign:hover {
        background: #059669;
    }

    .stats-bar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-card {
        background: white;
        padding: 1rem;
        border-radius: 0.5rem;
        border-left: 4px solid var(--dilg-success);
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .stat-value {
        font-size: 1.875rem;
        font-weight: bold;
        color: var(--dilg-success);
    }

    .stat-label {
        font-size: 0.75rem;
        color: #6b7280;
        text-transform: uppercase;
        margin-top: 0.25rem;
    }

    .alert-info {
        background: #d1fae5;
        border-left: 4px solid var(--dilg-success);
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
        color: #065f46;
        font-size: 0.875rem;
    }

    .pagination {
        padding: 1rem;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
    }

    .empty-state {
        padding: 3rem;
        text-align: center;
        color: #6b7280;
    }

    /* Assignment Modal */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }

    .modal.active {
        display: flex;
    }

    .modal-content {
        background: white;
        padding: 2rem;
        border-radius: 0.5rem;
        max-width: 500px;
        width: 90%;
    }

    .modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        color: var(--dilg-dark-gray);
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
        display: block;
    }

    .form-input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.875rem;
    }

    .modal-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }

    .btn-modal-cancel {
        background: #6b7280;
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.375rem;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-modal-submit {
        background: var(--dilg-dark-gold);
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.375rem;
        cursor: pointer;
        font-weight: 600;
    }
</style>

<div class="page-header">
    <h1 class="page-title">✅ Verified Reports</h1>
    <p class="page-subtitle">Reports verified by barangay staff ready for personnel assignment</p>
</div>

<!-- Stats Bar -->
<div class="stats-bar">
    <div class="stat-card">
        <div class="stat-value">{{ $reports->total() }}</div>
        <div class="stat-label">Verified Reports</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ \App\Models\ViolationReport::where('status', 'Verified')->whereNull('assigned_personnel')->count() }}</div>
        <div class="stat-label">Pending Assignment</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ \App\Models\ViolationReport::whereIn('status', ['Assigned', 'In Progress'])->count() }}</div>
        <div class="stat-label">In Progress</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ \App\Models\ViolationReport::where('status', 'Action Taken')->count() }}</div>
        <div class="stat-label">Action Taken</div>
    </div>
</div>

<div class="alert-info">
    ✅ <strong>Verified Reports:</strong> These reports have been verified as valid road clearing violations. 
    Assign personnel to handle each case and track progress until resolution.
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="{{ route('verified-reports.index') }}">
        <div class="filter-grid">
            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select name="status" class="filter-select">
                    <option value="">All Status</option>
                    <option value="Verified" {{ request('status') == 'Verified' ? 'selected' : '' }}>Verified</option>
                    <option value="Assigned" {{ request('status') == 'Assigned' ? 'selected' : '' }}>Assigned</option>
                    <option value="In Progress" {{ request('status') == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="Action Taken" {{ request('status') == 'Action Taken' ? 'selected' : '' }}>Action Taken</option>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Barangay</label>
                <select name="barangay" class="filter-select">
                    <option value="">All Barangays</option>
                    @foreach($barangays as $barangay)
                        <option value="{{ $barangay }}" {{ request('barangay') == $barangay ? 'selected' : '' }}>{{ $barangay }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Violation Type</label>
                <select name="violation_type" class="filter-select">
                    <option value="">All Types</option>
                    <option value="Illegal Parking" {{ request('violation_type') == 'Illegal Parking' ? 'selected' : '' }}>Illegal Parking</option>
                    <option value="Road Obstruction" {{ request('violation_type') == 'Road Obstruction' ? 'selected' : '' }}>Road Obstruction</option>
                    <option value="Sidewalk Obstruction" {{ request('violation_type') == 'Sidewalk Obstruction' ? 'selected' : '' }}>Sidewalk Obstruction</option>
                    <option value="Vending Obstruction" {{ request('violation_type') == 'Vending Obstruction' ? 'selected' : '' }}>Vending Obstruction</option>
                    <option value="Construction Obstruction" {{ request('violation_type') == 'Construction Obstruction' ? 'selected' : '' }}>Construction Obstruction</option>
                    <option value="Encroachment" {{ request('violation_type') == 'Encroachment' ? 'selected' : '' }}>Encroachment</option>
                    <option value="Abandoned Vehicle" {{ request('violation_type') == 'Abandoned Vehicle' ? 'selected' : '' }}>Abandoned Vehicle</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn-filter">🔍 Apply Filters</button>
    </form>
</div>

<!-- Reports Table -->
<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Report ID</th>
                    <th>Violation Type</th>
                    <th>Detected Barangay</th>
                    <th>Assigned Personnel</th>
                    <th>Status</th>
                    <th>Action Taken</th>
                    <th>Date Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $report)
                <tr>
                    <td><strong>{{ $report->report_id }}</strong></td>
                    <td>{{ $report->selected_violation_type }}</td>
                    <td>{{ $report->detected_barangay }}</td>
                    <td>
                        @if($report->assigned_personnel)
                            {{ $report->assigned_personnel }}
                        @else
                            <span style="color: #f59e0b;">Not Assigned</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ strtolower(str_replace(' ', '', $report->status)) }}">
                            {{ $report->status }}
                        </span>
                    </td>
                    <td>{{ $report->action_taken ? Str::limit($report->action_taken, 30) : 'No action yet' }}</td>
                    <td>{{ $report->date_updated ? $report->date_updated->format('M d, Y') : 'N/A' }}</td>
                    <td>
                        <a href="{{ route('violation-reports.show', $report) }}" class="btn-action btn-view">View</a>
                        @if(!$report->assigned_personnel)
                            <button onclick="openAssignModal({{ $report->id }}, '{{ $report->report_id }}')" class="btn-action btn-assign">Assign</button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="empty-state">
                        No verified reports found. Reports verified in Incoming Reports will appear here.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($reports->hasPages())
    <div class="pagination">
        {{ $reports->links() }}
    </div>
    @endif
</div>

<!-- Assignment Modal -->
<div id="assignModal" class="modal">
    <div class="modal-content">
        <h3 class="modal-title">👤 Assign Personnel</h3>
        <form id="assignForm" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Report ID</label>
                <input type="text" id="modalReportId" class="form-input" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Personnel Name <span style="color: #ef4444;">*</span></label>
                <input type="text" name="assigned_personnel" class="form-input" placeholder="Enter personnel name" required>
            </div>
            <div class="modal-actions">
                <button type="button" onclick="closeAssignModal()" class="btn-modal-cancel">Cancel</button>
                <button type="submit" class="btn-modal-submit">Assign Personnel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAssignModal(reportId, reportIdText) {
        const modal = document.getElementById('assignModal');
        const form = document.getElementById('assignForm');
        const reportIdInput = document.getElementById('modalReportId');
        
        form.action = `/verified-reports/${reportId}/assign`;
        reportIdInput.value = reportIdText;
        modal.classList.add('active');
    }

    function closeAssignModal() {
        const modal = document.getElementById('assignModal');
        modal.classList.remove('active');
    }

    // Close modal on outside click
    document.getElementById('assignModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeAssignModal();
        }
    });
</script>

@endsection
