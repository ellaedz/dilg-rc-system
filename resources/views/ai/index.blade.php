@extends('layouts.app')

@section('title', 'AI Analytics - DILG-RC System')

@section('content')
<style>
    .page-header {
        margin-bottom: 2rem;
    }

    .page-title {
        font-size: 2rem;
        font-weight: bold;
        color: #333;
        margin-bottom: 0.5rem;
    }

    .page-subtitle {
        color: #666;
        font-size: 0.9375rem;
    }

    .placeholder-notice {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border: 2px solid #F4C542;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
        text-align: center;
    }

    .placeholder-notice-title {
        font-size: 1.5rem;
        font-weight: bold;
        color: #92400e;
        margin-bottom: 0.5rem;
    }

    .placeholder-notice-text {
        color: #78350f;
        font-size: 1rem;
    }

    .modules-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .module-card {
        background: white;
        border-radius: 0.75rem;
        padding: 2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        text-align: center;
        transition: all 0.3s;
        border: 2px solid transparent;
    }

    .module-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        border-color: #F4C542;
    }

    .module-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
    }

    .module-name {
        font-size: 1.5rem;
        font-weight: bold;
        color: #333;
        margin-bottom: 0.75rem;
    }

    .module-status {
        display: inline-block;
        padding: 0.5rem 1rem;
        background: #fee2e2;
        color: #991b1b;
        border-radius: 1rem;
        font-size: 0.875rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .module-description {
        color: #6b7280;
        font-size: 0.9375rem;
        line-height: 1.6;
    }

    .coming-soon {
        margin-top: 2rem;
        padding: 2rem;
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        text-align: center;
    }

    .coming-soon-title {
        font-size: 1.5rem;
        font-weight: bold;
        color: #333;
        margin-bottom: 0.5rem;
    }

    .coming-soon-text {
        color: #6b7280;
        font-size: 1rem;
    }
</style>

<div class="page-header">
    <h1 class="page-title">🤖 AI Analytics Dashboard</h1>
    <p class="page-subtitle">Artificial Intelligence & Machine Learning Modules</p>
</div>

<div class="placeholder-notice">
    <div class="placeholder-notice-title">⚠️ Phase 3 Feature</div>
    <div class="placeholder-notice-text">
        AI Analytics modules will be integrated in Phase 3. Currently showing placeholder data.
    </div>
</div>

<div class="modules-grid">
    @foreach($modules as $module)
    <div class="module-card">
        <div class="module-icon">{{ $module['icon'] }}</div>
        <div class="module-name">{{ $module['name'] }}</div>
        <div class="module-status">{{ $module['status'] }}</div>
        <div class="module-description">{{ $module['description'] }}</div>
    </div>
    @endforeach
</div>

<div class="coming-soon">
    <div class="coming-soon-title">🚀 Coming in Phase 3</div>
    <div class="coming-soon-text">
        Features: Text Classification • Sentiment Analysis • Priority Prediction • Complaint Categorization • Trend Analysis • FastAPI Integration
    </div>
</div>
@endsection
