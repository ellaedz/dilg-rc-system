@extends('layouts.dilg-app')

@section('title', 'Dataset Manager - DILG-RC')

@section('content')
<style>
    .placeholder-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 60vh;
        padding: 3rem;
    }

    .placeholder-icon {
        font-size: 6rem;
        margin-bottom: 2rem;
        opacity: 0.5;
    }

    .placeholder-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--dilg-dark-gray);
        margin-bottom: 1rem;
    }

    .placeholder-message {
        font-size: 1.125rem;
        color: #6b7280;
        text-align: center;
        max-width: 600px;
        margin-bottom: 2rem;
    }

    .placeholder-features {
        background: white;
        padding: 2rem;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        max-width: 700px;
        width: 100%;
    }

    .feature-list {
        list-style: none;
        padding: 0;
    }

    .feature-item {
        padding: 1rem;
        border-left: 4px solid var(--dilg-yellow);
        margin-bottom: 1rem;
        background: #fef3c7;
        border-radius: 0.25rem;
    }

    .feature-title {
        font-weight: 600;
        color: var(--dilg-dark-gray);
        margin-bottom: 0.25rem;
    }

    .feature-desc {
        font-size: 0.875rem;
        color: #6b7280;
    }

    .btn-back {
        background: var(--dilg-dark-gold);
        color: white;
        padding: 0.75rem 2rem;
        border-radius: 0.5rem;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-block;
        margin-top: 2rem;
    }

    .btn-back:hover {
        background: var(--dilg-yellow);
        color: var(--dilg-dark-gray);
        transform: translateY(-2px);
    }
</style>

<div class="placeholder-container">
    <div class="placeholder-icon">💾</div>
    <h1 class="placeholder-title">Dataset Manager</h1>
    <p class="placeholder-message">
        Dataset management for AI model training will be available in Phase 4.
        This feature will help organize and prepare data for machine learning models.
    </p>

    <div class="placeholder-features">
        <h3 style="margin-bottom: 1.5rem; color: var(--dilg-dark-gold); font-size: 1.25rem;">📋 Planned Features</h3>
        <ul class="feature-list">
            <li class="feature-item">
                <div class="feature-title">📊 Data Collection</div>
                <div class="feature-desc">Automated collection of violation reports for training datasets</div>
            </li>
            <li class="feature-item">
                <div class="feature-title">🏷️ Data Labeling</div>
                <div class="feature-desc">Tools for annotating and categorizing violation data</div>
            </li>
            <li class="feature-item">
                <div class="feature-title">🧹 Data Cleaning</div>
                <div class="feature-desc">Remove duplicates, handle missing values, and validate data quality</div>
            </li>
            <li class="feature-item">
                <div class="feature-title">📈 Dataset Statistics</div>
                <div class="feature-desc">View distribution, balance, and quality metrics of datasets</div>
            </li>
            <li class="feature-item">
                <div class="feature-title">🔄 Data Preprocessing</div>
                <div class="feature-desc">Prepare data for NLP, computer vision, and ML model training</div>
            </li>
            <li class="feature-item">
                <div class="feature-title">💾 Export/Import</div>
                <div class="feature-desc">Export datasets in various formats (CSV, JSON, TensorFlow, PyTorch)</div>
            </li>
        </ul>
    </div>

    <a href="{{ route('dilg.dashboard') }}" class="btn-back">← Back to Dashboard</a>
</div>
@endsection
