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
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .hotspot-card {
        background: white;
        border-radius: 0.75rem;
        padding: 1.25rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border-left: 4px solid;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .hotspot-card.blue { border-color: #3b82f6; background: linear-gradient(135deg, #dbeafe 0%, #ffffff 100%); }
    .hotspot-card.purple { border-color: #a855f7; background: linear-gradient(135deg, #f3e8ff 0%, #ffffff 100%); }
    .hotspot-card.green { border-color: #10b981; background: linear-gradient(135deg, #d1fae5 0%, #ffffff 100%); }
    .hotspot-card.orange { border-color: #f59e0b; background: linear-gradient(135deg, #fef3c7 0%, #ffffff 100%); }

    .hotspot-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
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
        padding: 1.25rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 1.5rem;
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
        grid-template-columns: repeat(auto-fit, minmax(165px, 1fr));
        gap: 0.75rem;
        align-items: end;
    }

    .filter-field {
        display: flex;
        flex-direction: column;
    }

    .filter-label {
        font-size: 0.75rem;
        color: #6b7280;
        text-transform: uppercase;
        margin-bottom: 0.375rem;
        font-weight: 600;
    }

    .filter-select {
        height: 2rem;
        padding: 0 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.875rem;
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
        grid-template-columns: minmax(150px, auto) minmax(96px, auto);
        gap: 0.5rem;
        grid-column: 1 / -1;
        justify-content: end;
        min-width: 0;
    }

    .filter-btn {
        min-height: 2.5rem;
        padding: 0.625rem 1rem;
        border: none;
        border-radius: 0.5rem;
        font-size: 0.875rem;
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
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 1.5rem;
    }

    .map-card {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .card-header {
        padding: 1.25rem;
        border-bottom: 2px solid var(--dilg-yellow);
        background: #fefce8;
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
        height: 650px;
        background: #e5e7eb;
    }

    /* Sidebar */
    .sidebar-card {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        padding: 1.25rem;
        margin-bottom: 1.25rem;
    }

    .sidebar-card-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--dilg-dark-gray);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        padding: 0.625rem;
        margin-bottom: 0.5rem;
        background: #f9fafb;
        border-radius: 0.5rem;
    }

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
    .legend-symbol.outside-report { background: #64748b; border-color: #475569; }
    .legend-symbol.test-report { background: #64748b; border-color: #64748b; }

    .legend-label {
        font-size: 0.8125rem;
        color: var(--dilg-dark-gray);
        font-weight: 500;
    }

    /* Recommendation Panel */
    .recommendation-panel {
        background: white;
        border-radius: 0.75rem;
        padding: 1.25rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-top: 4px solid var(--dilg-yellow);
        display: none;
    }

    .rec-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
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
        margin-bottom: 0.875rem;
    }

    .rec-label {
        font-size: 0.6875rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.25rem;
        font-weight: 600;
    }

    .rec-value {
        font-size: 0.875rem;
        color: var(--dilg-dark-gray);
        font-weight: 600;
    }

    .rec-highlight {
        background: #fef3c7;
        padding: 0.75rem;
        border-radius: 0.5rem;
        border-left: 3px solid var(--dilg-yellow);
        margin-top: 1rem;
    }

    .rec-highlight-label {
        font-size: 0.6875rem;
        color: #92400e;
        text-transform: uppercase;
        margin-bottom: 0.375rem;
        font-weight: 600;
    }

    .rec-highlight-value {
        font-size: 0.9375rem;
        color: var(--dilg-dark-gold);
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
        <div style="position: relative;">
            <div id="map"></div>
            <div id="loading" class="loading-overlay" style="display: none;">
                <div class="loading-spinner">
                    <div class="spinner-icon">
                        <i class="fas fa-circle-notch fa-spin"></i>
                    </div>
                    <p style="margin-top: 1rem; color: var(--dilg-dark-gray);">Loading GIS data...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div>
        <!-- Legend Card -->
        <div class="sidebar-card">
            <h3 class="sidebar-card-title">
                <i class="fas fa-list"></i>
                Map Legend
            </h3>
            <div class="legend-item">
                <div class="legend-symbol boundary"></div>
                <div class="legend-label">{{ $isDilgAdmin ? 'MPDO Barangay Boundary' : 'Assigned MPDO Boundary' }}</div>
            </div>
            <div class="legend-item">
                <div class="legend-symbol report-state verified-valid">&#10003;</div>
                <div class="legend-label">Staff-verified valid violation</div>
            </div>
            <div class="legend-item">
                <div class="legend-symbol report-state ai-pending">A</div>
                <div class="legend-label">AI analysis pending</div>
            </div>
            <div class="legend-item">
                <div class="legend-symbol report-state pending-review">!</div>
                <div class="legend-label">Awaiting staff verification</div>
            </div>
            <div class="legend-item">
                <div class="legend-symbol report-state rejected-report">&times;</div>
                <div class="legend-label">Rejected or invalid report</div>
            </div>
            <div class="legend-item">
                <div class="legend-symbol report-state duplicate-report">D</div>
                <div class="legend-label">Duplicate report</div>
            </div>
            <div class="legend-item">
                <div class="legend-symbol report-state outside-report">O</div>
                <div class="legend-label">Outside supported jurisdiction</div>
            </div>
            <div class="legend-item">
                <div class="legend-symbol report-state test-report">T</div>
                <div class="legend-label">Test data</div>
            </div>
            <div class="legend-item">
                <div class="legend-symbol office"></div>
                <div class="legend-label">Verified Barangay Office</div>
            </div>
            <div class="legend-item">
                <div class="legend-symbol office" style="background:#2F80ED;border-color:#174EA6"></div>
                <div class="legend-label">Provisional Office Coordinate</div>
            </div>
        </div>

        <!-- Recommendation Panel -->
        <div class="recommendation-panel" id="recommendation-panel">
            <div class="rec-panel-header">
                <div class="rec-panel-title"><i class="fas fa-location-dot"></i> Report Details</div>
                <button class="rec-close-btn" onclick="closeRecommendationPanel()" aria-label="Close report details">&times;</button>
            </div>
            
            <div class="rec-field">
                <div class="rec-label">Tracking ID</div>
                <div class="rec-value" id="rec-tracking-id">-</div>
            </div>
            
            <div class="rec-field">
                <div class="rec-label">Detected Barangay</div>
                <div class="rec-value" id="rec-detected-barangay">-</div>
            </div>
            
            <div class="rec-field">
                <div class="rec-label">Report Status</div>
                <div class="rec-value" id="rec-report-status">-</div>
            </div>
            
            <div class="rec-highlight">
                <div class="rec-highlight-label">Recommended Barangay Office for Follow-up</div>
                <div class="rec-highlight-value" id="rec-office-name">-</div>
                <div style="font-size: 0.8125rem; color: #92400e; margin-top: 0.5rem;" id="rec-office-address">-</div>
                <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;" id="rec-office-validation">-</div>
            </div>
        </div>
    </div>
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
