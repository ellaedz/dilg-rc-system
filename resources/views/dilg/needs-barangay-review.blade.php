@extends('layouts.dilg-app')

@section('title', 'Needs Barangay Review - DILG-RC')

@section('content')
<div class="space-y-5">
    <div class="page-header">
        <div class="dashboard-eyebrow text-[#9a720d]">Municipal routing queue</div>
        <h1 class="page-title">Needs Barangay Review</h1>
        <p class="page-subtitle">Review and temporarily route reports that cannot be assigned without barangay polygons.</p>
    </div>

    <div class="alert alert-warning shadow-sm">
        <i class="fas fa-triangle-exclamation"></i>
        <span>Barangay assignment is temporarily reviewed by DILG because barangay-level boundary data is not yet available.</span>
    </div>

    @if($errors->any())
        <div class="alert alert-error">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="overflow-x-auto bg-white rounded-2xl shadow-sm border border-gray-200">
        <table class="table table-zebra">
            <thead class="bg-gray-50">
                <tr>
                    <th>Tracking ID</th>
                    <th>Violation Type</th>
                    <th>GPS Coordinates</th>
                    <th>Municipality Status</th>
                    <th>Date Submitted</th>
                    <th>Routing Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $report)
                    <tr>
                        <td><a class="link link-primary font-semibold" href="{{ route('violation-reports.show', $report) }}">{{ $report->report_id }}</a></td>
                        <td>{{ $report->selected_violation_type }}</td>
                        <td class="font-mono text-xs">{{ $report->latitude }}, {{ $report->longitude }}</td>
                        <td>
                            @if($report->municipality_validated)
                                <span class="badge badge-success">Inside Santa Cruz</span>
                            @else
                                <span class="badge badge-error">Outside Coverage</span>
                            @endif
                        </td>
                        <td>{{ $report->date_submitted?->format('M d, Y') }}</td>
                        <td><x-status-badge status="Needs Barangay Review" size="sm" /></td>
                        <td>
                            @if($report->municipality_validated)
                            <button class="btn btn-sm btn-primary" onclick="document.getElementById('route-{{ $report->id }}').showModal()">Route Report</button>
                            <dialog id="route-{{ $report->id }}" class="modal">
                                <div class="modal-box text-left">
                                    <h3 class="font-bold text-lg">Temporary DILG Routing</h3>
                                    <p class="py-2 text-sm text-gray-600">Route {{ $report->report_id }} only after reviewing its GPS location and evidence.</p>
                                    <form method="POST" action="{{ route('dilg.needs-barangay-review.route', $report) }}" class="space-y-4">
                                        @csrf
                                        <label class="form-control">
                                            <span class="label-text font-semibold">Selected barangay</span>
                                            <select name="selected_barangay" class="select select-bordered" required>
                                                <option value="">Select barangay</option>
                                                @foreach($barangays as $barangay)<option value="{{ $barangay }}">{{ $barangay }}</option>@endforeach
                                            </select>
                                        </label>
                                        <label class="form-control">
                                            <span class="label-text font-semibold">Assignment reason</span>
                                            <textarea name="assignment_reason" class="textarea textarea-bordered" minlength="10" required></textarea>
                                        </label>
                                        <label class="label cursor-pointer justify-start gap-3">
                                            <input type="checkbox" name="confirm_assignment" value="1" class="checkbox checkbox-warning" required>
                                            <span class="label-text">I confirm this temporary routing after reviewing the report.</span>
                                        </label>
                                        <div class="modal-action">
                                            <button type="button" class="btn" onclick="document.getElementById('route-{{ $report->id }}').close()">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Confirm Routing</button>
                                        </div>
                                    </form>
                                </div>
                            </dialog>
                            @else
                                <span class="text-xs text-error font-semibold">Cannot route outside coverage</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-12 text-gray-500">
                            <div class="font-semibold text-gray-700">No reports currently need barangay review.</div>
                            <div class="text-sm mt-2">New reports inside Santa Cruz will appear here when barangay polygons are unavailable.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $reports->links() }}
</div>
@endsection
