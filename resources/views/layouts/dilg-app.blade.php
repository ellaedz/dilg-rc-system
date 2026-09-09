<!DOCTYPE html>
<html lang="en" data-theme="dilg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b2c78">
    <title>@yield('title', 'CIVICLEAR')</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<div class="app-shell" data-app-shell>
    <button type="button" class="app-sidebar-backdrop" data-sidebar-close aria-label="Close navigation"></button>

    <aside class="app-sidebar" id="primary-navigation" aria-label="DILG administrator navigation">
        <div class="app-sidebar-brand">
            <img src="{{ asset('images/civiclear-logo.svg') }}" alt="CIVICLEAR logo">
            <div><h2>CIVICLEAR</h2><p>Santa Cruz, Laguna</p></div>
        </div>

        <nav class="app-nav">
            <div class="app-nav-label">Monitoring</div>
            <a href="{{ route('dilg.dashboard') }}" class="app-nav-link {{ request()->routeIs('dilg.dashboard') ? 'active' : '' }}"><i class="fas fa-table-cells-large"></i><span>Dashboard</span></a>
            <a href="{{ route('violation-reports.index') }}" class="app-nav-link {{ request()->routeIs('violation-reports.*') ? 'active' : '' }}"><i class="fas fa-clipboard-list"></i><span>Violation Reports</span></a>
            <a href="{{ route('dilg.needs-barangay-review.index') }}" class="app-nav-link {{ request()->routeIs('dilg.needs-barangay-review.*') ? 'active' : '' }}"><i class="fas fa-route"></i><span>Barangay Review</span></a>
            <a href="{{ route('response-tracking.index') }}" class="app-nav-link {{ request()->routeIs('response-tracking.*') ? 'active' : '' }}"><i class="fas fa-location-arrow"></i><span>Response Tracking</span></a>

            <div class="app-nav-label">Insights</div>
            <a href="{{ route('barangay-performance.index') }}" class="app-nav-link {{ request()->routeIs('barangay-performance.*') ? 'active' : '' }}"><i class="fas fa-ranking-star"></i><span>Performance</span></a>
            <a href="{{ route('analytics-reports.index') }}" class="app-nav-link {{ request()->routeIs('analytics-reports.*') ? 'active' : '' }}"><i class="fas fa-chart-column"></i><span>Analytics</span></a>
            <a href="{{ route('gis.index') }}" class="app-nav-link {{ request()->routeIs('gis.*') ? 'active' : '' }}"><i class="fas fa-map-location-dot"></i><span>GIS Map</span></a>

            <div class="app-nav-label">Account</div>
            <a href="{{ route('profile') }}" class="app-nav-link {{ request()->routeIs('profile') ? 'active' : '' }}"><i class="fas fa-user-gear"></i><span>Profile</span></a>
        </nav>

        <div class="app-sidebar-footer"><strong class="text-slate-300">CIVICLEAR</strong><br>Santa Cruz road-clearing portal &middot; 2026</div>
    </aside>

    <div class="app-main">
        <header class="app-topbar">
            <button type="button" class="btn btn-ghost btn-square app-menu-button" data-sidebar-toggle aria-controls="primary-navigation" aria-expanded="false" aria-label="Open navigation"><i class="fas fa-bars"></i></button>
            <div class="app-topbar-title"><h1>Road Clearing Operations</h1><p>Municipality-wide monitoring and response coordination</p></div>
            <span class="badge app-role-badge gap-2"><i class="fas fa-shield-halved"></i><span class="role-label">DILG Administrator</span></span>
            <div class="dropdown dropdown-end">
                <button type="button" tabindex="0" class="btn btn-ghost app-account-button" aria-label="Open Santa Cruz account menu">
                    <span class="app-account-place">Santa Cruz</span>
                    <span class="app-account-avatar">{{ strtoupper(substr(auth()->user()->email, 0, 1)) }}</span>
                </button>
                <ul tabindex="0" class="dropdown-content menu z-[100] mt-3 w-64 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
                    <li class="px-3 py-2"><span class="block truncate text-xs text-slate-500">{{ auth()->user()->email }}</span></li>
                    <li><a href="{{ route('profile') }}"><i class="fas fa-user"></i>Profile</a></li>
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
