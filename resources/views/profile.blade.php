@extends('layouts.app')

@section('title', 'Profile - DILG-RC System')

@section('content')
<style>
    .profile-container {
        max-width: 900px;
    }

    .profile-card {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .profile-header {
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        padding: 2rem;
        text-align: center;
        color: white;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: white;
        color: #1e40af;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: bold;
        margin: 0 auto 1rem;
        border: 4px solid rgba(255, 255, 255, 0.3);
    }

    .profile-name {
        font-size: 1.875rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }

    .profile-position {
        font-size: 1rem;
        opacity: 0.9;
    }

    .profile-body {
        padding: 2rem;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    .info-item {
        padding: 1rem 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .info-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem;
    }

    .info-value {
        font-size: 1rem;
        color: #1f2937;
        font-weight: 500;
    }

    .btn-primary {
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        margin-right: 1rem;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .btn-secondary {
        background: white;
        color: #374151;
        padding: 0.75rem 1.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-secondary:hover {
        background: #f9fafb;
        border-color: #3b82f6;
        color: #3b82f6;
    }

    .action-buttons {
        margin-top: 2rem;
        display: flex;
        gap: 1rem;
    }

    .section-title {
        font-size: 1.25rem;
        font-weight: bold;
        color: #1f2937;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #e5e7eb;
    }

    .stats-mini {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .stat-mini-card {
        background: #f9fafb;
        padding: 1.25rem;
        border-radius: 0.5rem;
        text-align: center;
    }

    .stat-mini-value {
        font-size: 1.875rem;
        font-weight: bold;
        color: #1e40af;
    }

    .stat-mini-label {
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
</style>

<div class="page-header">
    <h1 class="page-title">Profile</h1>
    <p class="page-subtitle">Manage your account information</p>
</div>

<div class="profile-container">
    <!-- Profile Card -->
    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar">{{ substr($user['name'], 0, 1) }}</div>
            <div class="profile-name">{{ $user['name'] }}</div>
            <div class="profile-position">{{ $user['position'] }}</div>
        </div>

        <div class="profile-body">
            <h3 class="section-title">Personal Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Employee ID</div>
                    <div class="info-value">{{ $user['employee_id'] }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email Address</div>
                    <div class="info-value">{{ $user['email'] }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Position</div>
                    <div class="info-value">{{ $user['position'] }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Office</div>
                    <div class="info-value">{{ $user['office'] }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Municipality</div>
                    <div class="info-value">{{ $user['municipality'] }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone Number</div>
                    <div class="info-value">{{ $user['phone'] }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Joined Date</div>
                    <div class="info-value">{{ date('F d, Y', strtotime($user['joined_date'])) }}</div>
                </div>
            </div>

            <div class="action-buttons">
                <button class="btn-primary">✏️ Edit Profile</button>
                <button class="btn-secondary">🔒 Change Password</button>
            </div>
        </div>
    </div>

    <!-- Activity Stats -->
    <div class="profile-card">
        <div class="profile-body">
            <h3 class="section-title">Your Activity Summary</h3>
            <div class="stats-mini">
                <div class="stat-mini-card">
                    <div class="stat-mini-value">45</div>
                    <div class="stat-mini-label">Records Created</div>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-value">12</div>
                    <div class="stat-mini-label">Cases Resolved</div>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-value">8</div>
                    <div class="stat-mini-label">Reports Generated</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
