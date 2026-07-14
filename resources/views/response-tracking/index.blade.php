@extends('layouts.dilg-app')

@section('title', 'Response Tracking - DILG-RC')

@section('content')
<div class="page-header">
    <div class="dashboard-eyebrow text-[#9a720d]">Municipal transparency</div>
    <h1 class="page-title">Response Tracking</h1>
    <p class="page-subtitle">Monitor response progress and accountability across all violation reports.</p>
</div>

<div class="alert alert-info shadow-sm mb-6">
    <i class="fas fa-info-circle text-xl"></i>
    <span><strong>Transparency Dashboard:</strong> Track real-time progress of violation reports from submission to resolution. Citizens can view this data to monitor government response times and actions taken.</span>
</div>

<!-- Status Cards - Compact Version -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <!-- Total Reports -->
    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg shadow-md border border-blue-200 p-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fas fa-file-alt text-white text-lg"></i>
            </div>
            <div>
                <div class="text-xs font-semibold text-blue-700 uppercase">Total</div>
                <div class="text-2xl font-bold text-blue-900">{{ $totalReports }}</div>
            </div>
        </div>
    </div>

    <!-- Pending -->
    <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg shadow-md border border-orange-200 p-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock text-white text-lg"></i>
            </div>
            <div>
                <div class="text-xs font-semibold text-orange-700 uppercase">Pending</div>
                <div class="text-2xl font-bold text-orange-900">{{ $pendingReports }}</div>
            </div>
        </div>
    </div>

    <!-- In Progress -->
    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg shadow-md border border-purple-200 p-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fas fa-spinner text-white text-lg"></i>
            </div>
            <div>
                <div class="text-xs font-semibold text-purple-700 uppercase">In Progress</div>
                <div class="text-2xl font-bold text-purple-900">{{ $inProgressReports }}</div>
            </div>
        </div>
    </div>

    <!-- Resolved -->
    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg shadow-md border border-green-200 p-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fas fa-check-circle text-white text-lg"></i>
            </div>
            <div>
                <div class="text-xs font-semibold text-green-700 uppercase">Resolved</div>
                <div class="text-2xl font-bold text-green-900">{{ $resolvedReports }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Performance Metrics -->
<div class="card bg-white shadow-lg mb-6">
    <div class="card-body">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Response Performance Metrics</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <div class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Average Response Time</div>
                <div class="text-3xl font-bold text-[#D4A017]">{{ $avgResponseTime }}</div>
                <div class="text-sm text-gray-600">days</div>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <div class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Reports This Month</div>
                <div class="text-3xl font-bold text-blue-600">{{ $monthlyReports }}</div>
                <div class="text-sm text-gray-600">reports</div>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <div class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Resolution Rate</div>
                <div class="text-3xl font-bold text-green-600">{{ $resolutionRate }}%</div>
                <div class="text-sm text-gray-600">success rate</div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card bg-white shadow-md mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('response-tracking.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="form-control flex-1 min-w-[200px]">
                <label class="label"><span class="label-text font-semibold text-xs">Search Report ID</span></label>
                <input type="text" name="search" class="input input-bordered input-sm" placeholder="RCV-2026-0001" value="{{ request('search') }}">
            </div>
            <div class="form-control flex-1 min-w-[150px]">
                <label class="label"><span class="label-text font-semibold text-xs">Status</span></label>
                <select name="status" class="select select-bordered select-sm">
                    <option value="">All Status</option>
                    <option value="Submitted" {{ request('status') == 'Submitted' ? 'selected' : '' }}>Submitted</option>
                    <option value="For Verification" {{ request('status') == 'For Verification' ? 'selected' : '' }}>For Verification</option>
                    <option value="Verified" {{ request('status') == 'Verified' ? 'selected' : '' }}>Verified</option>
                    <option value="Assigned" {{ request('status') == 'Assigned' ? 'selected' : '' }}>Assigned</option>
                    <option value="In Progress" {{ request('status') == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="Action Taken" {{ request('status') == 'Action Taken' ? 'selected' : '' }}>Action Taken</option>
                    <option value="Resolved" {{ request('status') == 'Resolved' ? 'selected' : '' }}>Resolved</option>
                </select>
            </div>
            <div class="form-control flex-1 min-w-[150px]">
                <label class="label"><span class="label-text font-semibold text-xs">Barangay</span></label>
                <select name="barangay" class="select select-bordered select-sm">
                    <option value="">All Barangays</option>
                    @foreach($barangays as $barangay)
                        <option value="{{ $barangay }}" {{ request('barangay') == $barangay ? 'selected' : '' }}>{{ $barangay }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-sm btn-primary bg-[#D4A017] hover:bg-[#F4C542] border-none text-white font-semibold h-8">
                    <i class="fas fa-search"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Status Tabs - Invoice Style -->
<div class="tabs tabs-boxed bg-white shadow-md mb-6 p-2">
    <a href="?status=" class="tab {{ !request('status') ? 'tab-active' : '' }}">All ({{ $totalReports }})</a>
    <a href="?status=Submitted" class="tab {{ request('status') == 'Submitted' ? 'tab-active' : '' }} text-blue-600">Submitted ({{ \App\Models\ViolationReport::where('status', 'Submitted')->count() }})</a>
    <a href="?status=In Progress" class="tab {{ request('status') == 'In Progress' ? 'tab-active' : '' }} text-purple-600">In Progress ({{ $inProgressReports }})</a>
    <a href="?status=Resolved" class="tab {{ request('status') == 'Resolved' ? 'tab-active' : '' }} text-green-600">Resolved ({{ $resolvedReports }})</a>
</div>

<!-- Reports List - Compact Card Style (Desktop Only) -->
<div class="space-y-3">
    @forelse($reports as $report)
    <div class="card bg-white shadow-sm hover:shadow-md transition-all duration-200 border-l-4 {{ 
        $report->status == 'Resolved' ? 'border-green-500' : 
        ($report->status == 'In Progress' ? 'border-purple-500' : 
        ($report->status == 'Submitted' ? 'border-blue-500' : 'border-orange-500')) 
    }}">
        <div class="card-body p-4">
            <!-- Header Row -->
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">{{ $report->report_id }}</h3>
                        <p class="text-xs text-gray-500">{{ $report->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                    <div class="text-sm">
                        <span class="text-gray-600">{{ $report->selected_violation_type }}</span>
                        <span class="text-gray-400 mx-2">•</span>
                        <span class="text-gray-600">{{ $report->detected_barangay }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <x-status-badge :status="$report->status" />
                    <a href="{{ route('violation-reports.show', $report) }}" class="btn btn-xs btn-ghost text-blue-600">
                        <i class="fas fa-eye"></i> View
                    </a>
                </div>
            </div>

            <!-- Compact Info Row -->
            <div class="flex items-center gap-6 text-xs text-gray-600">
                <div class="flex items-center gap-1">
                    <i class="fas fa-user text-gray-400"></i>
                    <span>{{ $report->assigned_personnel ?? 'Unassigned' }}</span>
                </div>
                <div class="flex items-center gap-1">
                    <i class="fas fa-clock text-gray-400"></i>
                    <span>{{ $report->updated_at->diffForHumans() }}</span>
                </div>
                @if($report->action_taken)
                <div class="flex items-center gap-1 flex-1 truncate">
                    <i class="fas fa-info-circle text-gray-400"></i>
                    <span class="truncate">{{ Str::limit($report->action_taken, 50) }}</span>
                </div>
                @endif
            </div>

            <!-- Compact Progress Timeline -->
            <div class="mt-3 pt-3 border-t border-gray-100">
                <ul class="steps steps-horizontal w-full text-xs">
                    <li class="step step-xs {{ in_array($report->status, ['Submitted', 'For Verification', 'Verified', 'Assigned', 'In Progress', 'Action Taken', 'Resolved']) ? 'step-primary' : '' }}">Submitted</li>
                    <li class="step step-xs {{ in_array($report->status, ['Verified', 'Assigned', 'In Progress', 'Action Taken', 'Resolved']) ? 'step-primary' : '' }}">Verified</li>
                    <li class="step step-xs {{ in_array($report->status, ['Assigned', 'In Progress', 'Action Taken', 'Resolved']) ? 'step-primary' : '' }}">Assigned</li>
                    <li class="step step-xs {{ in_array($report->status, ['In Progress', 'Action Taken', 'Resolved']) ? 'step-primary' : '' }}">In Progress</li>
                    <li class="step step-xs {{ in_array($report->status, ['Action Taken', 'Resolved']) ? 'step-primary' : '' }}">Action Taken</li>
                    <li class="step step-xs {{ $report->status == 'Resolved' ? 'step-primary' : '' }}">Resolved</li>
                </ul>
            </div>
        </div>
    </div>
    @empty
    <div class="card bg-white shadow-md">
        <div class="card-body text-center py-12">
            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No Reports Found</h3>
            <p class="text-gray-500">Reports submitted from the mobile app will appear here for tracking.</p>
        </div>
    </div>
    @endforelse
</div>

<!-- Pagination -->
@if($reports->hasPages())
<div class="mt-6 flex justify-center">
    {{ $reports->links() }}
</div>
@endif

@endsection
