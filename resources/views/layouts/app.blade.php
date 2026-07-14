<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'DILG-RC System')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }

        /* DILG Color Palette */
        :root {
            --dilg-yellow: #F4C542;
            --dilg-dark-gold: #D4A017;
            --dilg-dark-gray: #333333;
            --dilg-light-gray: #f5f5f5;
            --dilg-white: #ffffff;
            --dilg-success: #10b981;
            --dilg-warning: #f59e0b;
            --dilg-error: #ef4444;
        }

        /* Topbar */
        .topbar {
            background: var(--dilg-white);
            border-bottom: 4px solid var(--dilg-yellow);
            color: var(--dilg-dark-gray);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .dilg-logo {
            font-size: 2rem;
        }

        .topbar-title {
            display: flex;
            flex-direction: column;
        }

        .topbar-title-main {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--dilg-dark-gold);
            line-height: 1.2;
        }

        .topbar-title-sub {
            font-size: 0.75rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--dilg-yellow);
            color: var(--dilg-dark-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.125rem;
        }

        .user-name {
            font-weight: 500;
            color: var(--dilg-dark-gray);
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 68px;
            width: 260px;
            height: calc(100vh - 68px);
            background: var(--dilg-dark-gray);
            box-shadow: 2px 0 8px rgba(0,0,0,0.1);
            overflow-y: auto;
        }

        .sidebar-menu {
            list-style: none;
            padding: 1.5rem 0;
        }

        .sidebar-menu li {
            margin: 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: #cccccc;
            text-decoration: none;
            transition: all 0.3s;
            gap: 0.875rem;
            border-left: 4px solid transparent;
        }

        .sidebar-menu a:hover {
            background: rgba(244, 197, 66, 0.1);
            color: var(--dilg-yellow);
        }

        .sidebar-menu a.active {
            background: rgba(244, 197, 66, 0.15);
            color: var(--dilg-yellow);
            border-left: 4px solid var(--dilg-yellow);
            font-weight: 600;
        }

        .menu-icon {
            font-size: 1.25rem;
            width: 24px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            margin-top: 68px;
            padding: 2rem;
            min-height: calc(100vh - 68px);
        }

        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: bold;
            color: var(--dilg-dark-gray);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 0.9375rem;
        }

        /* Logout Button */
        .btn-logout {
            background: var(--dilg-dark-gold);
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background: var(--dilg-yellow);
            color: var(--dilg-dark-gray);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(212, 160, 23, 0.3);
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d1fae5;
            border-color: var(--dilg-success);
            color: #065f46;
        }

        .alert-warning {
            background: #fef3c7;
            border-color: var(--dilg-warning);
            color: #92400e;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
            }
        }
    </style>
</head>
<body>
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <div class="dilg-logo">🏛️</div>
            <div class="topbar-title">
                <div class="topbar-title-main">DILG-RC</div>
                <div class="topbar-title-sub">Road Clearing Violation Reporting</div>
            </div>
        </div>
        <div class="topbar-right">
            <div class="topbar-user">
                <div class="user-avatar">A</div>
                <div>
                    <div class="user-name">Admin User</div>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn-logout">Logout</button>
            </form>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <ul class="sidebar-menu">
            <li>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="menu-icon">📊</span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="{{ route('violation-reports.index') }}" class="{{ request()->routeIs('violation-reports.*') ? 'active' : '' }}">
                    <span class="menu-icon">⚠️</span>
                    <span>Violation Reports</span>
                </a>
            </li>
            <li>
                <a href="{{ route('incoming-reports.index') }}" class="{{ request()->routeIs('incoming-reports.*') ? 'active' : '' }}">
                    <span class="menu-icon">📥</span>
                    <span>Incoming Reports</span>
                </a>
            </li>
            <li>
                <a href="{{ route('verified-reports.index') }}" class="{{ request()->routeIs('verified-reports.*') ? 'active' : '' }}">
                    <span class="menu-icon">✅</span>
                    <span>Verified Reports</span>
                </a>
            </li>
            <li>
                <a href="{{ route('response-tracking.index') }}" class="{{ request()->routeIs('response-tracking.*') ? 'active' : '' }}">
                    <span class="menu-icon">📍</span>
                    <span>Response Tracking</span>
                </a>
            </li>
            <li>
                <a href="{{ route('analytics-reports.index') }}" class="{{ request()->routeIs('analytics-reports.*') ? 'active' : '' }}">
                    <span class="menu-icon">📈</span>
                    <span>Analytics & Reports</span>
                </a>
            </li>
            <li>
                <a href="{{ route('ai.index') }}" class="{{ request()->routeIs('ai.*') ? 'active' : '' }}">
                    <span class="menu-icon">🤖</span>
                    <span>AI Analytics</span>
                </a>
            </li>
            <li>
                <a href="{{ route('gis.index') }}" class="{{ request()->routeIs('gis.*') ? 'active' : '' }}">
                    <span class="menu-icon">🗺️</span>
                    <span>GIS Map</span>
                </a>
            </li>
            <li>
                <a href="{{ route('dataset-manager.index') }}" class="{{ request()->routeIs('dataset-manager.*') ? 'active' : '' }}">
                    <span class="menu-icon">💾</span>
                    <span>Dataset Manager</span>
                </a>
            </li>
            <li>
                <a href="{{ route('profile') }}" class="{{ request()->routeIs('profile') ? 'active' : '' }}">
                    <span class="menu-icon">👤</span>
                    <span>Profile</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </div>
</body>
</html>
