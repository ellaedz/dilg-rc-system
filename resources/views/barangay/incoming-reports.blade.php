@extends('layouts.barangay-app')

@section('title', 'Incoming Reports - CIVICLEAR')

@section('content')
<div class="page-header flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <div class="dashboard-eyebrow text-[#174ea6]">Verification queue</div>
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
        <div class="dashboard-metric-label">Photo available on page</div><div class="dashboard-metric-value">{{ number_format($reports->filter(fn ($report) => $report->photo_object_key || $report->image_path)->count()) }}</div>
    </article>
</section>

<div role="note" class="alert mb-5 border border-blue-200 bg-blue-50 text-blue-900 shadow-sm">
    <i class="fas fa-circle-info text-blue-600" aria-hidden="true"></i>
    <span><strong>Before verifying:</strong> confirm the photo evidence, GPS location, description, and that the incident belongs to {{ $barangay }}.</span>
</div>

@if($errors->any())
    <div role="alert" class="alert alert-error mb-5 text-white">
        <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

<div class="grid gap-4">
    @forelse($reports as $report)
        @php
            $aiPrediction = $report->final_ai_prediction ?: $report->ai_possible_violation;
            $aiOfficialType = \App\Support\OfficialViolationType::fromAi($aiPrediction);
            $aiLabel = \App\Support\OfficialViolationType::label($aiPrediction, 'Waiting for AI analysis');
            $aiConfidence = $report->final_ai_confidence ?? $report->ai_possible_violation_confidence;
        @endphp
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
                        <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Violation type</dt><dd class="mt-1 font-semibold text-slate-800">{{ $report->citizen_violation_type_label }}</dd></div>
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
                    @if($report->photo_object_key)
                        <a href="{{ route('violation-reports.photo', $report) }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                            <img src="{{ route('violation-reports.photo', $report) }}" alt="Evidence for report {{ $report->report_id }}" class="h-56 w-full object-cover transition-transform hover:scale-[1.02] lg:h-full lg:min-h-56">
                        </a>
                    @elseif($report->image_path)
                        <div class="grid h-56 place-items-center rounded-xl border-2 border-dashed border-amber-300 bg-amber-50 text-center text-sm text-amber-800">
                            <span><i class="fas fa-lock mb-2 block text-3xl" aria-hidden="true"></i>Legacy photo requires private migration</span>
                        </div>
                    @else
                        <div class="grid h-56 place-items-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 text-center text-sm text-slate-500">
                            <span><i class="far fa-image mb-2 block text-3xl text-slate-300" aria-hidden="true"></i>No photo evidence</span>
                        </div>
                    @endif
                </div>
            </div>

            <section class="border-t border-slate-200 bg-blue-50/60 px-4 py-4 sm:px-5" aria-label="AI recommendation">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-blue-700">AI suggestion</div>
                        <div class="mt-1 text-lg font-extrabold text-slate-900">{{ $aiLabel }}</div>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs">
                        <span class="badge {{ $report->ai_processing_status === 'completed' ? 'badge-success' : 'badge-warning' }} h-auto py-2 text-white">
                            {{ $report->ai_processing_status === 'completed' ? 'Analysis complete' : 'Analysis '.$report->ai_processing_status }}
                        </span>
                        @if($aiConfidence !== null)
                            <span class="badge badge-info h-auto py-2 text-white">{{ number_format((float) $aiConfidence * 100, 1) }}% confidence</span>
                        @endif
                    </div>
                </div>
                <p class="mt-2 text-xs text-slate-600">This is a suggestion only. Barangay staff makes the official decision.</p>
            </section>

            @if(auth()->user()->role === 'barangay_staff')
                <footer class="grid gap-4 border-t border-slate-200 bg-slate-50 px-4 py-4 sm:px-5 lg:grid-cols-2">
                    <form action="{{ route('barangay.incoming-reports.verify', [$barangay, $report]) }}" method="POST" class="rounded-xl border border-emerald-200 bg-white p-4">
                        @csrf
                        <h3 class="font-bold text-emerald-800"><i class="fas fa-circle-check mr-1" aria-hidden="true"></i> Verify as a valid violation</h3>
                        <label for="official-type-{{ $report->id }}" class="mt-3 block text-xs font-bold text-slate-600">Official violation type</label>
                        <select id="official-type-{{ $report->id }}" name="official_violation_type" required class="select select-bordered mt-1 w-full bg-white">
                            <option value="">Select official type</option>
                            @foreach($officialViolationTypes as $type)
                                <option value="{{ $type }}" @selected(old('official_violation_type', $aiOfficialType) === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                        <label for="correction-{{ $report->id }}" class="mt-3 block text-xs font-bold text-slate-600">Why is it different? <span class="font-normal">Only needed when correcting AI</span></label>
                        <textarea id="correction-{{ $report->id }}" name="correction_reason" rows="2" maxlength="1000" class="textarea textarea-bordered mt-1 w-full bg-white" placeholder="Brief correction reason">{{ old('correction_reason') }}</textarea>
                        <button type="submit" class="btn btn-success mt-3 w-full text-white"><i class="fas fa-check" aria-hidden="true"></i> Confirm official class</button>
                    </form>

                    <form action="{{ route('barangay.incoming-reports.reject', [$barangay, $report]) }}" method="POST" class="rounded-xl border border-rose-200 bg-white p-4" onsubmit="return confirm('Submit this rejection decision for {{ $report->report_id }}?');">
                        @csrf
                        <h3 class="font-bold text-rose-800"><i class="fas fa-circle-xmark mr-1" aria-hidden="true"></i> Do not accept this report</h3>
                        <label for="rejection-{{ $report->id }}" class="mt-3 block text-xs font-bold text-slate-600">Decision</label>
                        <select id="rejection-{{ $report->id }}" name="verification_status" required class="select select-bordered mt-1 w-full bg-white">
                            <option value="">Select decision</option>
                            <option value="Invalid Report">Invalid report</option>
                            <option value="Duplicate">Duplicate report</option>
                            <option value="Outside Jurisdiction">Outside jurisdiction</option>
                            <option value="Insufficient Evidence">Insufficient evidence</option>
                        </select>
                        <label for="reason-{{ $report->id }}" class="mt-3 block text-xs font-bold text-slate-600">Reason</label>
                        <textarea id="reason-{{ $report->id }}" name="reason" rows="2" required maxlength="1000" class="textarea textarea-bordered mt-1 w-full bg-white" placeholder="Brief reason"></textarea>
                        <button type="submit" class="btn btn-outline btn-error mt-3 w-full"><i class="fas fa-xmark" aria-hidden="true"></i> Submit decision</button>
                    </form>
                </footer>
            @else
                <footer class="border-t border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600 sm:px-5">
                    DILG monitoring view. Only the assigned barangay staff can submit a verification decision.
                </footer>
            @endif
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
