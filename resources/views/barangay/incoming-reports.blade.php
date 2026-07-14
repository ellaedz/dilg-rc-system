@extends('layouts.barangay-app')

@section('title', 'Incoming Reports - DILG-RC')

@section('content')
<div class="page-header flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <div class="dashboard-eyebrow text-[#9a720d]">Verification queue</div>
        <h1 class="page-title">Incoming Reports</h1>
        <p class="page-subtitle">Review new road clearing reports assigned to {{ $barangay }}.</p>
    </div>
    <a href="{{ route('barangay.dashboard', $barangay) }}" class="btn btn-outline border-slate-300 bg-white"><i class="fas fa-arrow-left" aria-hidden="true"></i> Dashboard</a>
</div>

<section class="grid grid-cols-1 gap-3 mb-5 sm:grid-cols-3" aria-label="Incoming report summary">
    <article class="dashboard-metric !min-h-0" style="--metric-color:#ea580c;--metric-bg:#ffedd5">
        <div class="dashboard-metric-label">New submissions</div><div class="dashboard-metric-value">{{ number_format($reports->total()) }}</div>
    </article>
    <article class="dashboard-metric !min-h-0" style="--metric-color:#2563eb;--metric-bg:#dbeafe">
        <div class="dashboard-metric-label">GPS available on page</div><div class="dashboard-metric-value">{{ number_format($reports->whereNotNull('latitude')->count()) }}</div>
    </article>
    <article class="dashboard-metric !min-h-0" style="--metric-color:#7c3aed;--metric-bg:#ede9fe">
        <div class="dashboard-metric-label">Photo available on page</div><div class="dashboard-metric-value">{{ number_format($reports->whereNotNull('image_path')->count()) }}</div>
    </article>
</section>

<div role="note" class="alert mb-5 border border-blue-200 bg-blue-50 text-blue-900 shadow-sm">
    <i class="fas fa-circle-info text-blue-600" aria-hidden="true"></i>
    <span><strong>Before verifying:</strong> confirm the photo evidence, GPS location, description, and that the incident belongs to {{ $barangay }}.</span>
</div>

<div class="grid gap-4">
    @forelse($reports as $report)
        <article class="dashboard-panel">
            <header class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-extrabold text-slate-900">{{ $report->report_id }}</h2>
                        <x-status-badge :status="$report->status" size="sm" />
                    </div>
                    <p class="mt-1 text-xs text-slate-500"><i class="far fa-clock mr-1" aria-hidden="true"></i>{{ ($report->timestamp ?? $report->created_at)->format('M d, Y h:i A') }}</p>
                </div>
                <a href="{{ route('violation-reports.show', $report) }}" class="btn btn-sm btn-outline border-slate-300 bg-white"><i class="fas fa-eye" aria-hidden="true"></i> Full details</a>
            </header>

            <div class="grid gap-5 p-4 sm:p-5 lg:grid-cols-[minmax(0,1fr)_18rem]">
                <div>
                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Submitted by</dt><dd class="mt-1 font-semibold text-slate-800">{{ $report->submitted_by ?: 'Not provided' }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Contact</dt><dd class="mt-1 text-slate-700">{{ $report->contact_number ?: 'Not provided' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Violation type</dt><dd class="mt-1 font-semibold text-slate-800">{{ $report->selected_violation_type }}</dd></div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">GPS location</dt>
                            <dd class="mt-1">
                                @if($report->latitude && $report->longitude)
                                    <span class="badge badge-info h-auto gap-2 py-2 text-white"><i class="fas fa-location-dot" aria-hidden="true"></i>{{ number_format($report->latitude, 6) }}, {{ number_format($report->longitude, 6) }}</span>
                                @else
                                    <span class="badge badge-error h-auto gap-2 py-2 text-white"><i class="fas fa-location-crosshairs" aria-hidden="true"></i>No GPS data</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                    <div class="mt-5">
                        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Description</h3>
                        <p class="mt-2 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700">{{ $report->description ?: 'No description was provided.' }}</p>
                    </div>
                </div>

                <div>
                    @if($report->image_path)
                        <a href="{{ asset('storage/' . $report->image_path) }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                            <img src="{{ asset('storage/' . $report->image_path) }}" alt="Evidence for report {{ $report->report_id }}" class="h-56 w-full object-cover transition-transform hover:scale-[1.02] lg:h-full lg:min-h-56">
                        </a>
                    @else
                        <div class="grid h-56 place-items-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 text-center text-sm text-slate-500">
                            <span><i class="far fa-image mb-2 block text-3xl text-slate-300" aria-hidden="true"></i>No photo evidence</span>
                        </div>
                    @endif
                </div>
            </div>

            <footer class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-4 py-4 sm:flex-row sm:justify-end sm:px-5">
                <form action="{{ route('barangay.incoming-reports.reject', [$barangay, $report]) }}" method="POST" onsubmit="return confirm('Reject report {{ $report->report_id }}? This action will update its status.');">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-error w-full sm:w-auto"><i class="fas fa-xmark" aria-hidden="true"></i> Reject</button>
                </form>
                <form action="{{ route('barangay.incoming-reports.verify', [$barangay, $report]) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success w-full text-white sm:w-auto"><i class="fas fa-circle-check" aria-hidden="true"></i> Verify report</button>
                </form>
            </footer>
        </article>
    @empty
        <div class="dashboard-panel dashboard-empty">
            <i class="fas fa-inbox" aria-hidden="true"></i>
            <h2 class="font-bold text-slate-800">Inbox is clear</h2>
            <p class="mx-auto mt-2 max-w-lg">All incoming reports for {{ $barangay }} have been processed. New submissions will appear here automatically.</p>
        </div>
    @endforelse
</div>

@if($reports->hasPages())
    <nav class="mt-6 flex justify-center" aria-label="Incoming reports pages">{{ $reports->links() }}</nav>
@endif
@endsection
