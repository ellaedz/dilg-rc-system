@extends('layouts.barangay-app')

@section('title', 'Verified Reports - DILG-RC')

@section('content')
<div class="page-header flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <div class="dashboard-eyebrow text-[#9a720d]">Assignment queue</div>
        <h1 class="page-title">Verified Reports</h1>
        <p class="page-subtitle">Assign personnel and monitor confirmed violations for {{ $barangay }}.</p>
    </div>
    <a href="{{ route('barangay.incoming-reports', $barangay) }}" class="btn btn-outline border-slate-300 bg-white"><i class="fas fa-inbox" aria-hidden="true"></i> Incoming queue</a>
</div>

<section class="grid grid-cols-2 gap-3 mb-5 lg:grid-cols-4" aria-label="Verified report summary">
    @php
        $queueMetrics = [
            ['label' => 'Verified reports', 'value' => $reports->total(), 'color' => '#0891b2'],
            ['label' => 'Pending assignment', 'value' => $reports->whereNull('assigned_personnel')->count(), 'color' => '#ea580c'],
            ['label' => 'In progress on page', 'value' => $reports->whereIn('status', ['Assigned', 'In Progress'])->count(), 'color' => '#7c3aed'],
            ['label' => 'Action taken on page', 'value' => $reports->where('status', 'Action Taken')->count(), 'color' => '#059669'],
        ];
    @endphp
    @foreach($queueMetrics as $metric)
        <article class="dashboard-metric !min-h-0" style="--metric-color:{{ $metric['color'] }}">
            <div class="dashboard-metric-label">{{ $metric['label'] }}</div><div class="dashboard-metric-value">{{ number_format($metric['value']) }}</div>
        </article>
    @endforeach
</section>

<div role="note" class="alert mb-5 border border-emerald-200 bg-emerald-50 text-emerald-900 shadow-sm">
    <i class="fas fa-circle-check text-emerald-600" aria-hidden="true"></i>
    <span>These reports have passed verification. Assign an accountable person, then record field progress through response tracking.</span>
</div>

<section class="dashboard-panel" aria-labelledby="verified-list-title">
    <div class="dashboard-panel-header">
        <div><h2 id="verified-list-title" class="dashboard-panel-title">Reports ready for action</h2><p class="dashboard-panel-subtitle">Showing {{ $reports->firstItem() ?? 0 }}–{{ $reports->lastItem() ?? 0 }} of {{ number_format($reports->total()) }}</p></div>
        <a href="{{ route('barangay.response-tracking', $barangay) }}" class="btn btn-sm btn-ghost text-[#9a720d]">Response tracking <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </div>
    @if($reports->isEmpty())
        <div class="dashboard-empty">
            <i class="fas fa-user-check" aria-hidden="true"></i>
            <h2 class="font-bold text-slate-800">No verified reports yet</h2>
            <p class="mx-auto mt-2 max-w-lg">Reports approved from the incoming queue will appear here for personnel assignment.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead><tr><th>Report</th><th>Violation</th><th>Personnel</th><th>Status</th><th>Latest action</th><th>Updated</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @foreach($reports as $report)
                        <tr>
                            <td class="font-extrabold text-slate-800">{{ $report->report_id }}</td>
                            <td><span class="block max-w-52 truncate" title="{{ $report->selected_violation_type }}">{{ $report->selected_violation_type }}</span></td>
                            <td>
                                @if($report->assigned_personnel)
                                    <span class="font-semibold text-slate-700"><i class="far fa-user mr-1 text-slate-400" aria-hidden="true"></i>{{ $report->assigned_personnel }}</span>
                                @else
                                    <x-status-badge status="Unassigned" size="sm" />
                                @endif
                            </td>
                            <td><x-status-badge :status="$report->status" size="sm" /></td>
                            <td><span class="block max-w-48 truncate text-slate-600" title="{{ $report->action_taken }}">{{ $report->action_taken ? Str::limit($report->action_taken, 38) : 'No action recorded' }}</span></td>
                            <td class="whitespace-nowrap text-sm text-slate-500">{{ $report->date_updated ? $report->date_updated->format('M d, Y') : 'Not updated' }}</td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('violation-reports.show', $report) }}" class="btn btn-sm btn-ghost" aria-label="View report {{ $report->report_id }}"><i class="fas fa-eye" aria-hidden="true"></i></a>
                                    @if(!$report->assigned_personnel)
                                        <button type="button" class="btn btn-sm btn-success text-white js-assign-report" data-action="{{ route('barangay.verified-reports.assign', [$barangay, $report]) }}" data-report-id="{{ $report->report_id }}"><i class="fas fa-user-plus" aria-hidden="true"></i> Assign</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($reports->hasPages())
        <nav class="border-t border-slate-200 p-4" aria-label="Verified reports pages">{{ $reports->links() }}</nav>
    @endif
</section>

<dialog id="assignModal" class="modal" aria-labelledby="assign-modal-title">
    <div class="modal-box max-w-lg rounded-2xl p-0">
        <div class="border-b border-slate-200 px-6 py-5">
            <div class="flex items-start justify-between gap-4">
                <div><h2 id="assign-modal-title" class="text-lg font-extrabold text-slate-900">Assign personnel</h2><p class="mt-1 text-sm text-slate-500">Choose the person accountable for field action.</p></div>
                <button type="button" class="btn btn-sm btn-circle btn-ghost js-close-modal" aria-label="Close assignment dialog"><i class="fas fa-xmark" aria-hidden="true"></i></button>
            </div>
        </div>
        <form id="assignForm" method="POST" class="p-6">
            @csrf
            <label class="form-control w-full">
                <span class="label"><span class="label-text font-bold text-slate-700">Report ID</span></span>
                <input type="text" id="modalReportId" class="input input-bordered w-full bg-slate-50" readonly>
            </label>
            <label class="form-control mt-4 w-full">
                <span class="label"><span class="label-text font-bold text-slate-700">Personnel name <span class="text-red-600">*</span></span></span>
                <input type="text" name="assigned_personnel" class="input input-bordered w-full" placeholder="Enter full name" autocomplete="name" required autofocus>
            </label>
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" class="btn btn-ghost js-close-modal">Cancel</button>
                <button type="submit" class="btn bg-[#D4A017] hover:bg-[#b88810] border-none text-white"><i class="fas fa-user-check" aria-hidden="true"></i> Confirm assignment</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button aria-label="Close assignment dialog">close</button></form>
</dialog>

<script>
    (() => {
        const modal = document.getElementById('assignModal');
        const form = document.getElementById('assignForm');
        const reportIdInput = document.getElementById('modalReportId');
        const personnelInput = form.querySelector('[name="assigned_personnel"]');

        document.querySelectorAll('.js-assign-report').forEach(button => {
            button.addEventListener('click', () => {
                form.action = button.dataset.action;
                reportIdInput.value = button.dataset.reportId;
                personnelInput.value = '';
                modal.showModal();
                window.setTimeout(() => personnelInput.focus(), 50);
            });
        });
        document.querySelectorAll('.js-close-modal').forEach(button => button.addEventListener('click', () => modal.close()));
    })();
</script>
@endsection
