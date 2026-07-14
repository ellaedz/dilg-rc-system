@extends(auth()->user()->role === 'barangay_staff' ? 'layouts.barangay-app' : 'layouts.dilg-app')

@section('title', 'Account Profile - DILG-RC')

@section('content')
@php
    $user = auth()->user();
    $isBarangay = $user->role === 'barangay_staff';
    $assignedBarangay = $barangay ?? $user->assigned_barangay;
    $dashboardRoute = $isBarangay ? route('barangay.dashboard', $assignedBarangay) : route('dilg.dashboard');
@endphp

<div class="page-header flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <div class="dashboard-eyebrow text-[#9a720d]">Account information</div>
        <h1 class="page-title">User Profile</h1>
        <p class="page-subtitle">View your authenticated role and organizational assignment.</p>
    </div>
    <a href="{{ $dashboardRoute }}" class="btn btn-outline border-slate-300 bg-white"><i class="fas fa-arrow-left" aria-hidden="true"></i> Dashboard</a>
</div>

<div class="grid gap-5 lg:grid-cols-[20rem_minmax(0,1fr)]">
    <aside class="dashboard-panel">
        <div class="dashboard-panel-body text-center">
            <div class="mx-auto grid h-24 w-24 place-items-center rounded-full border-4 border-amber-200 bg-[#102b4c] text-3xl font-black text-[#f4c542] shadow-sm">{{ strtoupper(substr($user->email, 0, 1)) }}</div>
            <h2 class="mt-4 text-lg font-extrabold text-slate-900">{{ $isBarangay ? 'Barangay Staff' : 'DILG Administrator' }}</h2>
            <p class="mt-1 break-all text-sm text-slate-500">{{ $user->email }}</p>
            <div class="mt-4 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Active account</div>
        </div>
    </aside>

    <div class="grid gap-5">
        <section class="dashboard-panel">
            <div class="dashboard-panel-header"><div><h2 class="dashboard-panel-title">Official account details</h2><p class="dashboard-panel-subtitle">Information associated with your current login</p></div></div>
            <div class="dashboard-panel-body">
                <dl class="grid gap-5 sm:grid-cols-2">
                    <div><dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Email address</dt><dd class="mt-1.5 font-semibold text-slate-800">{{ $user->email }}</dd></div>
                    <div><dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">System role</dt><dd class="mt-1.5 font-semibold text-slate-800">{{ $isBarangay ? 'Barangay Staff' : 'DILG Administrator' }}</dd></div>
                    <div><dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Organization</dt><dd class="mt-1.5 font-semibold text-slate-800">{{ $isBarangay ? 'Barangay '.$assignedBarangay : 'DILG Santa Cruz, Laguna' }}</dd></div>
                    <div><dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Access scope</dt><dd class="mt-1.5 font-semibold text-slate-800">{{ $isBarangay ? 'Assigned barangay records' : 'Municipality-wide monitoring' }}</dd></div>
                </dl>
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="dashboard-panel-header"><div><h2 class="dashboard-panel-title">Account management</h2><p class="dashboard-panel-subtitle">Planned security and profile capabilities</p></div></div>
            <div class="dashboard-panel-body grid gap-3 sm:grid-cols-2">
                <div class="priority-item"><span class="priority-icon bg-blue-100 text-blue-700"><i class="fas fa-id-card" aria-hidden="true"></i></span><span class="priority-copy"><strong>Profile information</strong><span>Editing will be enabled in a future phase</span></span></div>
                <div class="priority-item"><span class="priority-icon bg-amber-100 text-amber-700"><i class="fas fa-key" aria-hidden="true"></i></span><span class="priority-copy"><strong>Password management</strong><span>Security controls are not yet available</span></span></div>
                <div class="priority-item"><span class="priority-icon bg-violet-100 text-violet-700"><i class="fas fa-bell" aria-hidden="true"></i></span><span class="priority-copy"><strong>Notifications</strong><span>Preferences are planned for a later phase</span></span></div>
                <div class="priority-item"><span class="priority-icon bg-emerald-100 text-emerald-700"><i class="fas fa-clock-rotate-left" aria-hidden="true"></i></span><span class="priority-copy"><strong>Activity history</strong><span>Audit history will remain read-only</span></span></div>
            </div>
        </section>
    </div>
</div>
@endsection
