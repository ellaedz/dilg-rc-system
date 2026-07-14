<!DOCTYPE html>
<html lang="en" data-theme="dilg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#172033">
    <title>@yield('title', 'DILG-RC System')</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
@php
    $isDilgBarangayView = auth()->user()->role === 'dilg_admin';
    $userBarangay = auth()->user()->assigned_barangay
        ?? request()->route('barangay')
        ?? data_get(config('santa_cruz_barangays.barangays.0'), 'name', 'Alipit');
    $barangayDisplay = ucwords(str_replace('-', ' ', $userBarangay));
    $profileRoute = $isDilgBarangayView
        ? route('profile')
        : route('barangay.profile', $userBarangay);
@endphp
<a href="#main-content" class="skip-link">Skip to main content</a>
<div class="app-shell" data-app-shell>
    <button type="button" class="app-sidebar-backdrop" data-sidebar-close aria-label="Close navigation"></button>

    <aside class="app-sidebar" id="primary-navigation" aria-label="Barangay staff navigation">
        <div class="app-sidebar-brand">
            <img src="{{ asset('images/dilg-logo.png') }}" alt="DILG logo">
            <div><h2>{{ $isDilgBarangayView ? 'Barangay Overview' : 'Barangay Portal' }}</h2><p>{{ $barangayDisplay }}</p></div>
        </div>

        <nav class="app-nav">
            @if($isDilgBarangayView)
                <div class="app-nav-label">DILG Administration</div>
                <a href="{{ route('dilg.dashboard') }}" class="app-nav-link"><i class="fas fa-arrow-left"></i><span>Back to DILG Overview</span></a>
            @endif
            <div class="app-nav-label">Workspace</div>
            <a href="{{ route('barangay.dashboard', $userBarangay) }}" class="app-nav-link {{ request()->routeIs('barangay.dashboard') ? 'active' : '' }}"><i class="fas fa-table-cells-large"></i><span>Dashboard</span></a>
            <a href="{{ route('barangay.incoming-reports', $userBarangay) }}" class="app-nav-link {{ request()->routeIs('barangay.incoming-reports*') ? 'active' : '' }}"><i class="fas fa-inbox"></i><span>Incoming Reports</span></a>
            <a href="{{ route('barangay.verified-reports', $userBarangay) }}" class="app-nav-link {{ request()->routeIs('barangay.verified-reports*') ? 'active' : '' }}"><i class="fas fa-clipboard-check"></i><span>Verified Reports</span></a>
            <a href="{{ route('barangay.response-tracking', $userBarangay) }}" class="app-nav-link {{ request()->routeIs('barangay.response-tracking') ? 'active' : '' }}"><i class="fas fa-location-arrow"></i><span>Response Tracking</span></a>

            <div class="app-nav-label">Insights</div>
            <a href="{{ route('barangay.analytics-reports', $userBarangay) }}" class="app-nav-link {{ request()->routeIs('barangay.analytics-reports') ? 'active' : '' }}"><i class="fas fa-chart-line"></i><span>Analytics</span></a>

            <div class="app-nav-label">Account</div>
            <a href="{{ $profileRoute }}" class="app-nav-link {{ request()->routeIs('profile') || request()->routeIs('barangay.profile') ? 'active' : '' }}"><i class="fas fa-user-gear"></i><span>Profile</span></a>
        </nav>

        <div class="app-sidebar-footer"><strong class="text-slate-300">DILG-RC</strong><br>Government monitoring portal &middot; 2026</div>
    </aside>

    <div class="app-main">
        <header class="app-topbar">
            <button type="button" class="btn btn-ghost btn-square app-menu-button" data-sidebar-toggle aria-controls="primary-navigation" aria-expanded="false" aria-label="Open navigation"><i class="fas fa-bars"></i></button>
            <div class="app-topbar-title"><h1>Barangay {{ $barangayDisplay }}</h1><p>Road-clearing verification and response workspace</p></div>
            <span class="badge badge-warning badge-outline gap-2"><i class="fas {{ $isDilgBarangayView ? 'fa-shield-halved' : 'fa-user-tie' }}"></i><span class="role-label">{{ $isDilgBarangayView ? 'DILG Admin · Barangay View' : 'Barangay Staff' }}</span></span>
            <div class="dropdown dropdown-end">
                <button type="button" tabindex="0" class="btn btn-ghost btn-circle avatar" aria-label="Open account menu">
                    <div class="w-10 rounded-full bg-[#172033] text-[#F4C542] grid place-items-center font-bold">{{ strtoupper(substr(auth()->user()->email, 0, 1)) }}</div>
                </button>
                <ul tabindex="0" class="dropdown-content menu z-[100] mt-3 w-64 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
                    <li class="px-3 py-2"><span class="block truncate text-xs text-slate-500">{{ auth()->user()->email }}</span></li>
                    <li><a href="{{ $profileRoute }}"><i class="fas fa-user"></i>Profile</a></li>
                    <li><form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" class="w-full text-left text-error"><i class="fas fa-right-from-bracket"></i>Log out</button></form></li>
                </ul>
            </div>
        </header>

        <main id="main-content" class="app-content" tabindex="-1">
            @if(session('success'))<div class="alert alert-success app-flash" role="status"><i class="fas fa-circle-check"></i><span>{{ session('success') }}</span></div>@endif
            @if(session('error'))<div class="alert alert-error app-flash" role="alert"><i class="fas fa-circle-exclamation"></i><span>{{ session('error') }}</span></div>@endif
            @if(session('info'))<div class="alert alert-info app-flash" role="status"><i class="fas fa-circle-info"></i><span>{{ session('info') }}</span></div>@endif
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
