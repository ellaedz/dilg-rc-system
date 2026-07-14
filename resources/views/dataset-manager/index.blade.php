@extends('layouts.app')

@section('title', 'Dataset Manager - DILG-RC')

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
        color: #6b7280;
        font-size: 0.9375rem;
    }

    .placeholder-container {
        background: white;
        border-radius: 0.75rem;
        padding: 3rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        text-align: center;
    }

    .placeholder-icon {
        font-size: 6rem;
        margin-bottom: 1.5rem;
        opacity: 0.5;
    }

    .placeholder-title {
        font-size: 1.875rem;
        font-weight: bold;
        color: #333;
        margin-bottom: 1rem;
    }

    .placeholder-text {
        color: #6b7280;
        font-size: 1.125rem;
        margin-bottom: 2rem;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
        text-align: left;
    }

    .feature-card {
        background: #f9fafb;
        border-radius: 0.75rem;
        padding: 1.5rem;
        border-left: 4px solid #F4C542;
    }

    .feature-icon {
        font-size: 2rem;
        margin-bottom: 0.75rem;
    }

    .feature-title {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
    }

    .feature-desc {
        font-size: 0.9375rem;
        color: #6b7280;
        line-height: 1.6;
    }
</style>

<div class="page-header">
    <h1 class="page-title">💾 Dataset Manager</h1>
    <p class="page-subtitle">Manage training datasets for AI/ML models</p>
</div>

<div class="placeholder-container">
    <div class="placeholder-icon">💾</div>
    <h2 class="placeholder-title">Dataset Manager Coming Soon</h2>
    <p class="placeholder-text">
        This module will allow you to manage, organize, and prepare datasets for training AI and Machine Learning models.
    </p>

    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <div class="feature-title">Data Collection</div>
            <div class="feature-desc">
                Aggregate and organize concern data for ML training
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">🔍</div>
            <div class="feature-title">Data Preprocessing</div>
            <div class="feature-desc">
                Clean, normalize, and prepare data for model training
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">🏷️</div>
            <div class="feature-title">Data Labeling</div>
            <div class="feature-desc">
                Label and categorize concerns for supervised learning
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">📤</div>
            <div class="feature-title">Export Datasets</div>
            <div class="feature-desc">
                Export prepared datasets in various formats (CSV, JSON)
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">🔄</div>
            <div class="feature-title">Version Control</div>
            <div class="feature-desc">
                Track dataset versions and changes over time
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">📈</div>
            <div class="feature-title">Quality Metrics</div>
            <div class="feature-desc">
                Monitor dataset quality and completeness
            </div>
        </div>
    </div>
</div>

<div style="margin-top: 1.5rem; padding: 1rem; background: #fef3c7; border-radius: 0.5rem; border-left: 4px solid #F4C542; color: #78350f;">
    <strong>🚀 Coming in Phase 4:</strong> Dataset Manager will be fully functional after AI/ML integration in Phase 3 and will provide tools for managing training data for the NLP and prediction models.
</div>
@endsection
