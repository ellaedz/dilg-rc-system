@extends($isRequired ? 'layouts.security' : (auth()->user()->role === 'barangay_staff' ? 'layouts.barangay-app' : 'layouts.dilg-app'))

@section('title', 'Change Password - CIVICLEAR')

@section('content')
@php
    $user = auth()->user();
    $cancelRoute = $user->role === 'barangay_staff'
        ? route('barangay.profile', $user->assigned_barangay)
        : route('profile');
@endphp

<div class="mx-auto max-w-2xl">
    <div class="mb-6">
        <div class="dashboard-eyebrow text-[#174ea6]">{{ $isRequired ? 'Required security step' : 'Account security' }}</div>
        <h1 class="page-title">Create a private password</h1>
        <p class="page-subtitle">
            {{ $isRequired
                ? 'Your temporary password must be replaced before dashboards, reports, GIS, analytics, or exports can be opened.'
                : 'Changing your password will securely sign out every other active session.' }}
        </p>
    </div>

    <section class="dashboard-panel overflow-hidden">
        <div class="dashboard-panel-header">
            <div>
                <h2 class="dashboard-panel-title">Change password</h2>
                <p class="dashboard-panel-subtitle">Use at least 16 characters and a password not used recently.</p>
            </div>
            <span class="badge border-blue-200 bg-blue-50 font-semibold text-blue-700">{{ $user->email }}</span>
        </div>
        <form action="{{ route('password.update') }}" method="POST" class="dashboard-panel-body grid gap-5" novalidate>
            @csrf
            @method('PUT')

            @if($errors->any())
                <div class="alert alert-error" role="alert"><i class="fas fa-circle-exclamation"></i><span>{{ $errors->first() }}</span></div>
            @endif

            <div class="form-control">
                <label for="current_password" class="label"><span class="label-text font-semibold text-slate-700">Current password</span></label>
                <input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="input input-bordered w-full bg-white @error('current_password') input-error @enderror">
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="form-control">
                    <label for="password" class="label"><span class="label-text font-semibold text-slate-700">New password</span></label>
                    <input id="password" name="password" type="password" autocomplete="new-password" minlength="16" maxlength="128" required class="input input-bordered w-full bg-white @error('password') input-error @enderror">
                </div>
                <div class="form-control">
                    <label for="password_confirmation" class="label"><span class="label-text font-semibold text-slate-700">Confirm new password</span></label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="16" maxlength="128" required class="input input-bordered w-full bg-white">
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                CIVICLEAR checks length, known compromised values, the current password, and recent password history. Passwords remain one-way hashed and cannot be displayed by staff.
            </div>

            <div class="flex flex-wrap justify-end gap-3">
                @unless($isRequired)
                    <a href="{{ $cancelRoute }}" class="btn btn-ghost">Cancel</a>
                @endunless
                <button type="submit" class="btn border-none bg-[#174ea6] text-white hover:bg-[#123b91]"><i class="fas fa-shield-halved" aria-hidden="true"></i> Save new password</button>
            </div>
        </form>
    </section>
</div>
@endsection
