@extends($isDilgAdmin ? 'layouts.dilg-app' : 'layouts.barangay-app')

@section('title', 'GIS Monitoring Map - CIVICLEAR')

@section('content')
<style>
    :root {
        --dilg-yellow: #2F80ED;
        --dilg-dark-gold: #174EA6;
        --dilg-dark-gray: #333333;
        --dilg-white: #ffffff;
    }

    .page-header {
        margin-bottom: 1.5rem;
    }

    .page-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--dilg-dark-gray);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .page-subtitle {
        color: #6b7280;
        font-size: 1rem;
    }

    /* Hotspot Summary Cards */
    .hotspot-cards-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .hotspot-card {
        background: white;
        border-radius: 0.65rem;
        padding: 0.8rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border-left: 4px solid;
        display: flex;
        align-items: center;
        gap: 0.7rem;
    }

    .hotspot-card.blue { border-color: #3b82f6; background: linear-gradient(135deg, #dbeafe 0%, #ffffff 100%); }
    .hotspot-card.purple { border-color: #a855f7; background: linear-gradient(135deg, #f3e8ff 0%, #ffffff 100%); }
    .hotspot-card.green { border-color: #10b981; background: linear-gradient(135deg, #d1fae5 0%, #ffffff 100%); }
    .hotspot-card.orange { border-color: #f59e0b; background: linear-gradient(135deg, #fef3c7 0%, #ffffff 100%); }

    .hotspot-icon {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }

    .hotspot-card.blue .hotspot-icon { background: linear-gradient(135deg, #60a5fa, #3b82f6); color: white; }
    .hotspot-card.purple .hotspot-icon { background: linear-gradient(135deg, #c084fc, #a855f7); color: white; }
    .hotspot-card.green .hotspot-icon { background: linear-gradient(135deg, #34d399, #10b981); color: white; }
    .hotspot-card.orange .hotspot-icon { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; }

    .hotspot-content {
        flex: 1;
    }

    .hotspot-label {
        font-size: 0.75rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.25rem;
    }

    .hotspot-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dilg-dark-gray);
    }

    /* Filter Panel */
    .filter-panel {
        background: white;
        border-radius: 0.75rem;
        padding: 0.9rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 1rem;
        border-top: 3px solid var(--dilg-yellow);
    }

    .filter-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--dilg-dark-gray);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: 1.08fr 1.08fr 1.22fr 1fr 0.92fr 0.92fr auto;
        gap: 0.6rem;
        align-items: end;
    }

    .filter-field {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .filter-label {
        font-size: 0.75rem;
        color: #6b7280;
        text-transform: uppercase;
        margin-bottom: 0.375rem;
        font-weight: 600;
    }

    .filter-select {
        width: 100%;
        height: 2.65rem;
        padding: 0 0.65rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.82rem;
        color: var(--dilg-dark-gray);
    }

    .filter-select:focus {
        outline: none;
        border-color: var(--dilg-yellow);
        box-shadow: 0 0 0 2px rgba(47, 128, 237, 0.2);
    }

    .filter-select:disabled {
        background: #eff6ff;
        color: #174ea6;
        cursor: not-allowed;
        font-weight: 700;
    }

    .filter-buttons {
        display: grid;
        grid-template-columns: 132px 78px;
        gap: 0.5rem;
        justify-content: end;
        min-width: 0;
    }

    .filter-btn {
        min-height: 2.5rem;
        padding: 0.6rem 0.75rem;
        border: none;
        border-radius: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        line-height: 1.15;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        white-space: nowrap;
    }

    .filter-btn-apply {
        background: var(--dilg-yellow);
        color: #ffffff;
        box-shadow: 0 3px 8px rgba(47, 128, 237, 0.24);
    }

    .filter-btn-apply:hover {
        background: var(--dilg-dark-gold);
        transform: translateY(-1px);
    }

    .filter-btn-reset {
        background: #e5e7eb;
        color: #6b7280;
    }

    .filter-btn-reset:hover {
        background: #d1d5db;
    }

    /* Map Container */
    .map-container {
        position: relative;
        z-index: 0;
        isolation: isolate;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 380px;
        min-height: 680px;
        gap: 0;
        overflow: hidden;
        border: 1px solid #dbe4f0;
        border-radius: 1rem;
        background: #ffffff;
        box-shadow: 0 16px 45px rgba(15, 51, 96, 0.13);
    }

    .map-card {
        position: relative;
        background: white;
        border-radius: 1rem 0 0 1rem;
        box-shadow: none;
        overflow: hidden;
    }

    .card-header {
        position: relative;
        z-index: 1;
        min-height: 56px;
        padding: 0.85rem 1.1rem;
        border: 0;
        border-bottom: 1px solid #dbe4f0;
        background: #ffffff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--dilg-dark-gray);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .visible-count {
        font-size: 0.875rem;
        color: #6b7280;
    }

    .visible-count-number {
        font-weight: 700;
        color: var(--dilg-dark-gold);
    }

    #map {
        width: 100%;
        height: 624px;
        background: #e5e7eb;
    }

    .map-stage {
        position: relative;
        height: 624px;
    }

    /* Sidebar */
    .sidebar-card {
        position: absolute;
        left: 1rem;
        bottom: 1rem;
        z-index: 700;
        background: white;
        border: 1px solid #dbeafe;
        border-radius: 0.75rem;
        box-shadow: 0 10px 28px rgba(15, 51, 96, 0.18);
        padding: 0.55rem 0.65rem;
        margin: 0;
        max-width: calc(100% - 2rem);
    }

    .sidebar-card-title {
        font-size: 0.86rem;
        font-weight: 600;
        color: var(--dilg-dark-gray);
        margin-bottom: 0.4rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .legend-item {
        display: inline-flex;
        align-items: center;
        gap: 0.28rem;
        min-height: 24px;
        padding: 0.22rem 0.45rem 0.22rem 0.25rem;
        margin: 0;
        border: 1px solid transparent;
        border-radius: 999px;
        font-size: 0.66rem;
        font-weight: 700;
    }

    .legend-list {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.3rem;
    }

    .status-pill-icon {
        display: inline-grid;
        width: 16px;
        height: 16px;
        flex: 0 0 16px;
        place-items: center;
        border-radius: 50%;
        color: #ffffff;
        font-size: 0.52rem;
    }

    .status-pill.verified { border-color: #bbf7d0; background: #ecfdf5; color: #047857; }
    .status-pill.verified .status-pill-icon { background: #10b981; }
    .status-pill.ai-processing { border-color: #bfdbfe; background: #eff6ff; color: #1d4ed8; }
    .status-pill.ai-processing .status-pill-icon { background: #3b82f6; }
    .status-pill.awaiting { border-color: #fde68a; background: #fffbeb; color: #b45309; }
    .status-pill.awaiting .status-pill-icon { background: #f59e0b; }
    .status-pill.rejected { border-color: #fecaca; background: #fef2f2; color: #b91c1c; }
    .status-pill.rejected .status-pill-icon { background: #ef4444; }
    .status-pill.duplicate { border-color: #ddd6fe; background: #f5f3ff; color: #6d28d9; }
    .status-pill.duplicate .status-pill-icon { background: #8b5cf6; }

    .legend-symbol {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .legend-symbol.boundary {
        border: 3px solid var(--dilg-dark-gold);
        background: rgba(47, 128, 237, 0.1);
        border-radius: 0.25rem;
    }

    .legend-symbol.report-red { background: #ef4444; }
    .legend-symbol.report-orange { background: #f59e0b; }
    .legend-symbol.report-green { background: #10b981; }
    .legend-symbol.office { background: var(--dilg-yellow); border: 2px solid var(--dilg-dark-gold); }

    .legend-symbol.report-state {
        display: grid;
        place-items: center;
        color: #ffffff;
        font-size: 0.65rem;
        font-weight: 900;
        border: 3px solid;
    }

    .legend-symbol.verified-valid { background: #3b82f6; border-color: #059669; }
    .legend-symbol.ai-pending { background: #3b82f6; border-color: #0ea5e9; }
    .legend-symbol.pending-review { background: #3b82f6; border-color: #f59e0b; }
    .legend-symbol.rejected-report { background: #64748b; border-color: #dc2626; }
    .legend-symbol.duplicate-report { background: #64748b; border-color: #7c3aed; }

    .legend-label {
        color: inherit;
        font-size: inherit;
        font-weight: inherit;
    }

    /* Recommendation Panel */
    .recommendation-panel {
        background: transparent;
        padding: 0;
        box-shadow: none;
        border: 0;
        display: flex;
        flex: 1;
        flex-direction: column;
    }

    .gis-side-column {
        display: flex;
        flex-direction: column;
        min-width: 0;
        padding: 1.6rem;
        border-left: 1px solid #dbe4f0;
        background: linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
    }

    .gis-side-eyebrow {
        color: #2563eb;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .gis-side-title {
        margin: 0.25rem 0 0.35rem;
        color: #102a4c;
        font-size: 1.35rem;
        font-weight: 800;
    }

    .gis-side-description {
        margin-bottom: 1.25rem;
        color: #64748b;
        font-size: 0.875rem;
        line-height: 1.5;
    }

    .report-panel-empty {
        display: flex;
        flex: 1;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 360px;
        padding: 2rem;
        color: #64748b;
        text-align: center;
    }

    .report-panel-empty[hidden] { display: none; }

    .report-panel-empty i {
        display: grid;
        width: 64px;
        height: 64px;
        margin-bottom: 1rem;
        place-items: center;
        border-radius: 50%;
        background: #eaf3ff;
        color: #2563eb;
        font-size: 1.5rem;
    }

    .report-panel-content[hidden] { display: none; }

    .report-panel-content {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 0.6rem;
        padding-top: 0.9rem;
        border-top: 1px solid #e2e8f0;
    }

    .rec-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        grid-column: 1 / -1;
    }

    .rec-panel-title {
        font-size: 0.9375rem;
        font-weight: 600;
        color: var(--dilg-dark-gray);
    }

    .rec-close-btn {
        background: none;
        border: none;
        font-size: 1.25rem;
        color: #6b7280;
        cursor: pointer;
        padding: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .rec-close-btn:hover {
        color: var(--dilg-dark-gray);
    }

    .rec-field {
        min-width: 0;
        margin: 0;
        padding: 0.65rem 0.8rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.65rem;
        background: #ffffff;
    }

    .rec-label {
        font-size: 0.72rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.38rem;
        font-weight: 600;
    }

    .rec-value {
        overflow-wrap: break-word;
        font-size: 0.92rem;
        line-height: 1.5;
        color: var(--dilg-dark-gray);
        font-weight: 600;
    }

    .rec-highlight {
        background: #eaf3ff;
        padding: 0.75rem;
        border-radius: 0.5rem;
        border-left: 3px solid var(--dilg-yellow);
        margin-top: 1rem;
        grid-column: 1 / -1;
    }

    .rec-highlight-label {
        font-size: 0.6875rem;
        color: #1d4ed8;
        text-transform: uppercase;
        margin-bottom: 0.375rem;
        font-weight: 600;
    }

    .rec-highlight-value {
        font-size: 0.9375rem;
        color: #102a4c;
        font-weight: 700;
    }

    /* Loading Overlay */
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .loading-spinner {
        text-align: center;
    }

    .spinner-icon {
        font-size: 2.5rem;
        color: var(--dilg-dark-gold);
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Leaflet Popup Custom Styling */
    .leaflet-popup-content-wrapper {
        border-radius: 0.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .leaflet-popup-pane { z-index: 800; }

    .barangay-popup {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .barangay-popup-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--dilg-dark-gray);
        margin-bottom: 0.25rem;
    }

    .barangay-popup-subtitle {
        font-size: 0.75rem;
        color: #6b7280;
    }

    @media (max-width: 1180px) {
        .hotspot-cards-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .map-container {
            grid-template-columns: 1fr;
        }

        .gis-side-column {
            min-height: 420px;
            border-top: 1px solid #dbe4f0;
            border-left: 0;
        }
    }

    @media (max-width: 900px) {
        .filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .filter-buttons { grid-column: 1 / -1; }
        .legend-list { flex-wrap: wrap; }
    }

    @media (max-width: 640px) {
        .page-title {
            font-size: 1.5rem;
        }

        .hotspot-cards-grid {
            grid-template-columns: 1fr;
        }

        .filter-buttons {
            grid-template-columns: 1fr 1fr;
            justify-content: stretch;
        }

        .filter-grid { grid-template-columns: 1fr; }
        .filter-field { grid-column: 1 / -1; }

        .filter-btn {
            width: 100%;
        }

        .card-header {
            align-items: flex-start;
            flex-direction: column;
            gap: 0.5rem;
        }

        #map {
            height: 520px;
        }

        .map-stage { height: 520px; }

        .card-header { padding: 0.75rem; }

        .sidebar-card {
            right: 0.75rem;
            bottom: 0.75rem;
            left: 0.75rem;
            max-width: none;
        }

        .report-panel-content { grid-template-columns: 1fr; }
    }
</style>

<!-- Leaflet CSS (Local) -->
<link rel="stylesheet" href="{{ asset('css/leaflet.css') }}" />

<link rel="stylesheet" href="{{ asset('vendor/leaflet.markercluster/MarkerCluster.css') }}" />
<link rel="stylesheet" href="{{ asset('vendor/leaflet.markercluster/MarkerCluster.Default.css') }}" />

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-map-marked-alt"></i>
        {{ $isDilgAdmin ? 'Road Clearing GIS Monitoring Map' : 'Barangay '.$mapScopeBarangay.' GIS Workspace' }}
    </h1>
    <p class="page-subtitle">
        @if($isDilgAdmin)
            Santa Cruz, Laguna &mdash; report clustering, hotspots, and follow-up office recommendations
        @else
            Assigned-barangay reports only &mdash; secured map monitoring for Barangay {{ $mapScopeBarangay }}
        @endif
    </p>
</div>

@php
    $officeCoordinateData = collect(config('santa_cruz_barangay_halls', []));
    if (!$isDilgAdmin) {
        $officeCoordinateData = $officeCoordinateData->filter(
            fn (array $office) => strcasecmp((string) ($office['barangay'] ?? ''), (string) $mapScopeBarangay) === 0
        );
    }
    $verifiedOfficeCount = $officeCoordinateData->where('validation_status', 'Verified')->count();
    $provisionalOfficeCount = $officeCoordinateData->count() - $verifiedOfficeCount;
@endphp
<div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
    <div class="alert bg-blue-50 border border-blue-200 text-blue-900 shadow-sm"><i class="fas fa-draw-polygon"></i><div><div class="font-bold">{{ $isDilgAdmin ? $barangayCount.' MPDO barangay boundaries' : 'Barangay '.$mapScopeBarangay.' boundary' }}</div><div class="text-xs">{{ $isDilgAdmin ? 'Verified polygons are active for GPS assignment.' : 'Your assigned polygon is highlighted; neighboring polygons are map context only.' }}</div></div></div>
    <div class="alert bg-emerald-50 border border-emerald-200 text-emerald-900 shadow-sm"><i class="fas fa-circle-check"></i><div><div class="font-bold">{{ $verifiedOfficeCount }} verified offices</div><div class="text-xs">Researcher-ready coordinates imported.</div></div></div>
    <div class="alert bg-amber-50 border border-amber-200 text-amber-900 shadow-sm"><i class="fas fa-triangle-exclamation"></i><div><div class="font-bold">{{ $provisionalOfficeCount }} provisional offices</div><div class="text-xs">Retained fallbacks still require validation.</div></div></div>
</div>

<!-- Hotspot Summary Cards -->
<div class="hotspot-cards-grid">
    <div class="hotspot-card blue">
        <div class="hotspot-icon"><i class="fas fa-chart-simple"></i></div>
        <div class="hotspot-content">
            <div class="hotspot-label" id="mapped-reports-label">Operational Mapped Reports</div>
            <div class="hotspot-value" id="total-mapped-reports">0</div>
        </div>
    </div>
    
    <div class="hotspot-card purple">
        <div class="hotspot-icon"><i class="fas fa-fire-flame-curved"></i></div>
        <div class="hotspot-content">
            <div class="hotspot-label">{{ $isDilgAdmin ? 'Top Hotspot Barangay' : 'Assigned Barangay' }}</div>
            <div class="hotspot-value" id="top-hotspot-barangay" style="font-size: 1.125rem;">{{ $mapScopeBarangay ?? 'N/A' }}</div>
        </div>
    </div>
    
    <div class="hotspot-card green">
        <div class="hotspot-icon"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="hotspot-content">
            <div class="hotspot-label">Most Common Violation</div>
            <div class="hotspot-value" id="most-common-violation" style="font-size: 0.9375rem;">N/A</div>
        </div>
    </div>
    
    <div class="hotspot-card orange">
        <div class="hotspot-icon"><i class="fas fa-arrow-trend-up"></i></div>
        <div class="hotspot-content">
            <div class="hotspot-label">Most Common Status</div>
            <div class="hotspot-value" id="most-common-status" style="font-size: 1.125rem;">N/A</div>
        </div>
    </div>
</div>

<!-- Filter Panel -->
<div class="filter-panel">
    <div class="filter-title">
        <i class="fas fa-filter"></i>
        Filter Reports
    </div>
    <div class="filter-grid">
        <div class="filter-field">
            <label class="filter-label" for="filter-dataset">Map Dataset</label>
            <select class="filter-select" id="filter-dataset">
                <option value="operational">Operational reports</option>
                <option value="official">Official verified statistics</option>
            </select>
        </div>

        <div class="filter-field">
            <label class="filter-label">Barangay</label>
            <select class="filter-select" id="filter-barangay" @disabled(!$isDilgAdmin)>
                @if($isDilgAdmin)
                    <option value="">All Barangays</option>
                    @foreach(config('santa_cruz_barangays.barangays', []) as $barangayData)
                        <option value="{{ $barangayData['name'] }}">{{ $barangayData['name'] }}</option>
                    @endforeach
                @else
                    <option value="{{ $mapScopeBarangay }}" selected>{{ $mapScopeBarangay }}</option>
                @endif
            </select>
        </div>
        
        <div class="filter-field">
            <label class="filter-label">Violation Type</label>
            <select class="filter-select" id="filter-violation-type">
                <option value="">All Violations</option>
                @foreach(config('santa_cruz_barangays.violation_types', []) as $violationType)
                    <option value="{{ $violationType }}">{{ $violationType }}</option>
                @endforeach
            </select>
        </div>
        
        <div class="filter-field">
            <label class="filter-label">Status</label>
            <select class="filter-select" id="filter-status">
                <option value="">All Statuses</option>
                @foreach(config('santa_cruz_barangays.statuses', []) as $status)
                    <option value="{{ $status }}">{{ $status }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label class="filter-label" for="filter-date-from">Date From</label>
            <input class="filter-select" type="date" id="filter-date-from">
        </div>

        <div class="filter-field">
            <label class="filter-label" for="filter-date-to">Date To</label>
            <input class="filter-select" type="date" id="filter-date-to">
        </div>
        
        <div class="filter-buttons">
            <button class="filter-btn filter-btn-apply" id="apply-filters-btn">
                <i class="fas fa-check"></i> Apply Filters
            </button>
            <button class="filter-btn filter-btn-reset" id="reset-filters-btn">
                <i class="fas fa-redo"></i> Reset
            </button>
        </div>
    </div>
</div>

<!-- Map and Sidebar -->
<div class="map-container">
    <!-- Main Map -->
    <div class="map-card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-map"></i>
                {{ $isDilgAdmin ? 'Interactive Municipal GIS Map' : 'Barangay '.$mapScopeBarangay.' Operational Map' }}
            </h2>
            <div class="visible-count">
                Visible Markers: <span class="visible-count-number" id="visible-markers-count">0</span>
            </div>
        </div>
        <div class="map-stage">
            <div id="map"></div>
            <div id="loading" class="loading-overlay" style="display: none;">
                <div class="loading-spinner">
                    <div class="spinner-icon">
                        <i class="fas fa-circle-notch fa-spin"></i>
                    </div>
                    <p style="margin-top: 1rem; color: var(--dilg-dark-gray);">Loading GIS data...</p>
                </div>
            </div>
            <div class="sidebar-card" aria-label="Report status symbols">
                <h3 class="sidebar-card-title">
                    <i class="fas fa-file-circle-check"></i>
                    Report Status
                </h3>
                <div class="legend-list">
                    <div class="legend-item status-pill verified">
                        <span class="status-pill-icon"><i class="fas fa-check"></i></span>
                        <span class="legend-label">Verified</span>
                    </div>
                    <div class="legend-item status-pill ai-processing">
                        <span class="status-pill-icon"><i class="fas fa-wand-magic-sparkles"></i></span>
                        <span class="legend-label">AI Processing</span>
                    </div>
                    <div class="legend-item status-pill awaiting">
                        <span class="status-pill-icon"><i class="fas fa-clock"></i></span>
                        <span class="legend-label">Awaiting Review</span>
                    </div>
                    <div class="legend-item status-pill rejected">
                        <span class="status-pill-icon"><i class="fas fa-xmark"></i></span>
                        <span class="legend-label">Rejected</span>
                    </div>
                    <div class="legend-item status-pill duplicate">
                        <span class="status-pill-icon"><i class="fas fa-copy"></i></span>
                        <span class="legend-label">Duplicate</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Selected report inspector -->
    <aside class="gis-side-column" aria-label="Selected report information">
        <div class="gis-side-eyebrow">GIS Report Inspector</div>
        <h2 class="gis-side-title">Selected Report</h2>
        <p class="gis-side-description">Choose a marker to review its classification, location, status, and assigned follow-up office.</p>

        <div class="recommendation-panel" id="recommendation-panel">
            <div class="report-panel-empty" id="report-panel-empty">
                <i class="fas fa-location-crosshairs"></i>
                <strong>No report selected</strong>
                <span>Tap or click a report marker on the map.</span>
            </div>

            <div class="report-panel-content" id="report-panel-content" hidden>
                <div class="rec-panel-header">
                    <div class="rec-panel-title"><i class="fas fa-file-lines"></i> Report Details</div>
                    <button class="rec-close-btn" onclick="closeRecommendationPanel()" aria-label="Clear selected report">&times;</button>
                </div>

                <div class="rec-field">
                    <div class="rec-label">Tracking ID</div>
                    <div class="rec-value" id="rec-tracking-id">-</div>
                </div>

                <div class="rec-field">
                    <div class="rec-label">Violation Type</div>
                    <div class="rec-value" id="rec-violation-type">-</div>
                </div>

                <div class="rec-field">
                    <div class="rec-label">Validation</div>
                    <div class="rec-value" id="rec-validation-state">-</div>
                </div>

                <div class="rec-field">
                    <div class="rec-label">Report Status</div>
                    <div class="rec-value" id="rec-report-status">-</div>
                </div>

                <div class="rec-field">
                    <div class="rec-label">Barangay</div>
                    <div class="rec-value" id="rec-detected-barangay">-</div>
                </div>

                <div class="rec-field">
                    <div class="rec-label">GPS Coordinates</div>
                    <div class="rec-value" id="rec-gps">-</div>
                </div>

                <div class="rec-highlight">
                    <div class="rec-highlight-label">Recommended Barangay Office for Follow-up</div>
                    <div class="rec-highlight-value" id="rec-office-name">-</div>
                    <div style="font-size: 0.8125rem; color: #1e3a5f; margin-top: 0.5rem;" id="rec-office-address">-</div>
                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;" id="rec-office-validation">-</div>
                </div>
            </div>
        </div>
    </aside>
</div>

<!-- Leaflet JS (Local) -->
<script src="{{ asset('js/leaflet.js') }}"></script>

<script src="{{ asset('vendor/leaflet.markercluster/leaflet.markercluster.js') }}"></script>

<!-- GIS markers -->
<script src="{{ asset('js/gis-markers.js') }}"></script>

<script>
    // Default center coordinates for Santa Cruz, Laguna
    const DEFAULT_CENTER = [{{ $defaultCenter['lat'] }}, {{ $defaultCenter['lng'] }}];
    const DEFAULT_ZOOM = 14;
    
    const BARANGAY_GEOJSON_URL = @json($barangayGeojsonUrl);
    const BARANGAY_GEOJSON_EXISTS = {{ $barangayGeojsonExists ? 'true' : 'false' }};
    const MUNICIPAL_GEOJSON_URL = @json($municipalGeojsonUrl);
    const MUNICIPAL_GEOJSON_EXISTS = {{ $municipalGeojsonExists ? 'true' : 'false' }};
    const MAP_SCOPE_BARANGAY = @json($mapScopeBarangay);
    window.CIVICLEAR_GIS_CONTEXT = {
        isDilgAdmin: {{ $isDilgAdmin ? 'true' : 'false' }},
        assignedBarangay: MAP_SCOPE_BARANGAY
    };

    // Initialize map
    const map = L.map('map', {
        center: DEFAULT_CENTER,
        zoom: DEFAULT_ZOOM,
        zoomControl: true,
        scrollWheelZoom: true,
        minZoom: 12,
        maxZoom: 18
    });

    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);

    function barangayBoundaryStyle(feature) {
        const barangayName = getBarangayName(feature?.properties);
        const isAssignedBoundary = MAP_SCOPE_BARANGAY
            && barangayName.localeCompare(MAP_SCOPE_BARANGAY, undefined, { sensitivity: 'accent' }) === 0;

        if (MAP_SCOPE_BARANGAY && !isAssignedBoundary) {
            return {
                fillColor: '#cbd5e1',
                weight: 1,
                opacity: 0.65,
                color: '#94a3b8',
                fillOpacity: 0.06
            };
        }

        return {
            fillColor: '#2F80ED',
            weight: isAssignedBoundary ? 4 : 2,
            opacity: 1,
            color: '#174EA6',
            fillOpacity: isAssignedBoundary ? 0.28 : 0.14
        };
    }

    function municipalBoundaryStyle() {
        return {
            fillOpacity: 0,
            weight: 4,
            opacity: 0.95,
            color: '#0B3B82',
            dashArray: '8 6'
        };
    }

    // Boundary hover style
    function highlightFeature(e) {
        const layer = e.target;
        const barangayName = getBarangayName(layer.feature?.properties);
        if (MAP_SCOPE_BARANGAY
            && barangayName.localeCompare(MAP_SCOPE_BARANGAY, undefined, { sensitivity: 'accent' }) !== 0) {
            return;
        }
        layer.setStyle({
            weight: 3,
            color: '#0B3B82',
            fillOpacity: 0.32
        });
        layer.bringToFront();
    }

    function resetHighlight(e) {
        if (window.geojsonLayer) {
            window.geojsonLayer.resetStyle(e.target);
        }
    }

    // Detect barangay name from GeoJSON properties
    function getBarangayName(properties) {
        const possibleKeys = [
            'name', 'Name', 'NAME',
            'barangay', 'Barangay', 'BARANGAY',
            'brgy', 'Brgy', 'BRGY',
            'BGY_NAME', 'BRGY_NAME',
            'ADM4_EN', 'ADM4_NAME',
            'NAME_4', 'NAME_3'
        ];

        for (let key of possibleKeys) {
            if (properties && properties[key]) {
                return properties[key];
            }
        }

        return 'Barangay boundary';
    }

    function escapeHtml(value) {
        const element = document.createElement('div');
        element.textContent = value ?? '';
        return element.innerHTML;
    }

    function onEachFeature(feature, layer) {
        const barangayName = getBarangayName(feature.properties);
        const isAssignedBoundary = !MAP_SCOPE_BARANGAY
            || barangayName.localeCompare(MAP_SCOPE_BARANGAY, undefined, { sensitivity: 'accent' }) === 0;

        if (!isAssignedBoundary) {
            return;
        }

        const psgc = feature.properties?.PSGC || 'Not available';
        const area = Number(feature.properties?.area);
        const areaText = Number.isFinite(area) ? `${(area / 1000000).toFixed(2)} km²` : 'Not available';

        const popupContent = `
            <div class="barangay-popup">
                <div class="barangay-popup-title">Barangay ${escapeHtml(barangayName)}</div>
                <div class="barangay-popup-subtitle">Santa Cruz, Laguna</div>
                <div class="barangay-popup-subtitle">PSGC: ${escapeHtml(psgc)}</div>
                <div class="barangay-popup-subtitle">Mapped area: ${escapeHtml(areaText)}</div>
            </div>
        `;
        
        layer.bindPopup(popupContent);

        layer.on({
            mouseover: highlightFeature,
            mouseout: resetHighlight
        });
    }


    let geojsonLayer;
    window.geojsonLayer = null;

    async function fetchGeoJson(url, label) {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Failed to load ${label} (${response.status})`);
        }

        return response.json();
    }

    async function initializeBoundaryLayers() {
        const loading = document.getElementById('loading');
        loading.style.display = 'flex';

        try {
            if (MUNICIPAL_GEOJSON_EXISTS) {
                const municipalData = await fetchGeoJson(MUNICIPAL_GEOJSON_URL, 'municipal boundary');
                L.geoJSON(municipalData, {
                    style: municipalBoundaryStyle,
                    interactive: false
                }).addTo(map);
            }

            if (!BARANGAY_GEOJSON_EXISTS) {
                throw new Error('The MPDO barangay boundary file is unavailable.');
            }

            const barangayData = await fetchGeoJson(BARANGAY_GEOJSON_URL, 'barangay boundaries');
            geojsonLayer = L.geoJSON(barangayData, {
                style: barangayBoundaryStyle,
                onEachFeature
            }).addTo(map);
            window.geojsonLayer = geojsonLayer;

            let bounds = geojsonLayer.getBounds();
            if (MAP_SCOPE_BARANGAY) {
                const assignedBounds = L.latLngBounds();
                geojsonLayer.eachLayer(layer => {
                    const layerName = getBarangayName(layer.feature?.properties);
                    if (layerName.localeCompare(MAP_SCOPE_BARANGAY, undefined, { sensitivity: 'accent' }) === 0) {
                        assignedBounds.extend(layer.getBounds());
                    }
                });

                if (assignedBounds.isValid()) {
                    bounds = assignedBounds;
                }
            }
            if (bounds.isValid()) {
                map.fitBounds(bounds, { padding: [24, 24], maxZoom: MAP_SCOPE_BARANGAY ? 16 : 13 });
                map.setMaxBounds(bounds.pad(MAP_SCOPE_BARANGAY ? 1.2 : 0.2));
            }

            console.info(`Loaded ${barangayData.features?.length || 0} MPDO barangay boundaries.`);
        } catch (error) {
            console.error('GIS boundary loading failed:', error);
            L.popup()
                .setLatLng(DEFAULT_CENTER)
                .setContent('<div style="text-align:center"><strong style="color:#b91c1c">Barangay boundaries could not be loaded.</strong></div>')
                .openOn(map);
        } finally {
            loading.style.display = 'none';
            initializeGISMarkers(map);
        }
    }

    initializeBoundaryLayers();
</script>
@endsection
