@extends('layouts.dilg-app')

@section('title', 'Account Management - CIVICLEAR')

@section('content')
<div class="page-header flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <div class="dashboard-eyebrow text-[#174ea6]">DILG administrator only</div>
        <h1 class="page-title">Barangay Account Management</h1>
        <p class="page-subtitle">Review assigned barangays and issue a temporary password without exposing any existing password.</p>
    </div>
    <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
        <strong>{{ $accounts->count() }}</strong> barangay staff accounts
    </div>
</div>

@if($errors->any())
    <div class="alert alert-error mb-5" role="alert"><i class="fas fa-circle-exclamation"></i><span>{{ $errors->first() }}</span></div>
@endif

<section class="dashboard-panel overflow-hidden">
    <div class="dashboard-panel-header">
        <div><h2 class="dashboard-panel-title">Authorized barangay accounts</h2><p class="dashboard-panel-subtitle">Temporary passwords are entered by the authorized DILG office and are never shown again.</p></div>
    </div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th>Assigned barangay</th>
                    <th>Account</th>
                    <th>Account status</th>
                    <th>Password status</th>
                    <th>Last changed</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                    @php
                        $isLocked = $account->locked_until?->isFuture();
                        $accountLabel = ! $account->is_active ? 'Inactive' : ($isLocked ? 'Temporarily locked' : 'Active');
                        $accountClass = ! $account->is_active || $isLocked
                            ? 'border-amber-200 bg-amber-50 text-amber-700'
                            : 'border-emerald-200 bg-emerald-50 text-emerald-700';
                    @endphp
                    <tr>
                        <td><strong class="text-slate-900">{{ $account->assigned_barangay }}</strong></td>
                        <td><span class="block font-medium text-slate-800">{{ $account->name }}</span><span class="text-xs text-slate-500">{{ $account->email }}</span></td>
                        <td><span class="badge {{ $accountClass }}">{{ $accountLabel }}</span></td>
                        <td>
                            @if($account->must_change_password)
                                <span class="badge border-amber-200 bg-amber-50 text-amber-700"><i class="fas fa-clock" aria-hidden="true"></i> Change required</span>
                            @else
                                <span class="badge border-slate-200 bg-slate-50 text-slate-600"><i class="fas fa-check" aria-hidden="true"></i> Current</span>
                            @endif
                        </td>
                        <td class="text-sm text-slate-600">{{ $account->password_changed_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? 'Not recorded' }}</td>
                        <td>
                            <details class="dropdown dropdown-end">
                                <summary class="btn btn-sm border-slate-300 bg-white text-[#174ea6]"><i class="fas fa-key" aria-hidden="true"></i> Reset</summary>
                                <div class="dropdown-content z-[80] mt-2 w-[min(24rem,calc(100vw-2rem))] rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl">
                                    <h3 class="font-bold text-slate-900">Reset {{ $account->assigned_barangay }}</h3>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">Enter a unique temporary password privately. All active sessions for this account will be invalidated.</p>
                                    <form action="{{ route('account-management.password.reset', $account) }}" method="POST" class="mt-4 grid gap-3" novalidate>
                                        @csrf
                                        @method('PUT')
                                        <label class="form-control">
                                            <span class="label-text mb-1 font-semibold">Temporary password</span>
                                            <input name="password" type="password" autocomplete="new-password" minlength="16" maxlength="128" required class="input input-bordered w-full bg-white">
                                        </label>
                                        <label class="form-control">
                                            <span class="label-text mb-1 font-semibold">Confirm temporary password</span>
                                            <input name="password_confirmation" type="password" autocomplete="new-password" minlength="16" maxlength="128" required class="input input-bordered w-full bg-white">
                                        </label>
                                        <button type="submit" class="btn mt-1 border-none bg-[#174ea6] text-white hover:bg-[#123b91]">Reset and require change</button>
                                    </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-slate-500">No barangay staff accounts were found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<div class="mt-5 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600">
    <i class="fas fa-shield-halved mr-2 text-[#174ea6]" aria-hidden="true"></i>
    Existing passwords cannot be retrieved. Do not place temporary passwords in chat, email, screenshots, PDFs, browser URLs, exports, or support tickets.
</div>
@endsection
