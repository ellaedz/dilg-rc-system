@extends($isDilgAdmin ? 'layouts.dilg-app' : 'layouts.barangay-app')

@section('title', 'GIS Monitoring Map - DILG-RC')

@section('content')
<style>
    :root {
        --dilg-yellow: #F4C542;
        --dilg-dark-gold: #D4A017;
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
        grid-template-columns: repeat(3, 1fr) auto;
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
        box-shadow: 0 0 0 2px rgba(244, 197, 66, 0.2);
    }

    .filter-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .filter-btn {
        height: 2rem;
        padding: 0 1rem;
        border: none;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .filter-btn-apply {
        background: var(--dilg-yellow);
        color: var(--dilg-dark-gray);
    }

    .filter-btn-apply:hover {
        background: var(--dilg-dark-gold);
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
        background: rgba(244, 197, 66, 0.1);
        border-radius: 0.25rem;
    }

    .legend-symbol.report-red { background: #ef4444; }
    .legend-symbol.report-orange { background: #f59e0b; }
    .legend-symbol.report-green { background: #10b981; }
    .legend-symbol.office { background: var(--dilg-yellow); border: 2px solid var(--dilg-dark-gold); }

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
</style>

<!-- Leaflet CSS (Local) -->
<link rel="stylesheet" href="{{ asset('css/leaflet.css') }}" />

<link rel="stylesheet" href="{{ asset('vendor/leaflet.markercluster/MarkerCluster.css') }}" />
<link rel="stylesheet" href="{{ asset('vendor/leaflet.markercluster/MarkerCluster.Default.css') }}" />

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-map-marked-alt"></i>
        Road Clearing GIS Monitoring Map
    </h1>
    <p class="page-subtitle">Santa Cruz, Laguna &mdash; report clustering, hotspots, and follow-up office recommendations</p>
</div>

@php
    $officeCoordinateData = collect(config('santa_cruz_barangay_halls', []));
    $verifiedOfficeCount = $officeCoordinateData->where('validation_status', 'Verified')->count();
    $provisionalOfficeCount = $officeCoordinateData->count() - $verifiedOfficeCount;
@endphp
<div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
    <div class="alert bg-blue-50 border border-blue-200 text-blue-900 shadow-sm"><i class="fas fa-draw-polygon"></i><div><div class="font-bold">Municipal boundary</div><div class="text-xs">Barangay polygons are not yet available.</div></div></div>
    <div class="alert bg-emerald-50 border border-emerald-200 text-emerald-900 shadow-sm"><i class="fas fa-circle-check"></i><div><div class="font-bold">{{ $verifiedOfficeCount }} verified offices</div><div class="text-xs">Researcher-ready coordinates imported.</div></div></div>
    <div class="alert bg-amber-50 border border-amber-200 text-amber-900 shadow-sm"><i class="fas fa-triangle-exclamation"></i><div><div class="font-bold">{{ $provisionalOfficeCount }} provisional offices</div><div class="text-xs">Retained fallbacks still require validation.</div></div></div>
</div>

<!-- Hotspot Summary Cards -->
<div class="hotspot-cards-grid">
    <div class="hotspot-card blue">
        <div class="hotspot-icon"><i class="fas fa-chart-simple"></i></div>
        <div class="hotspot-content">
            <div class="hotspot-label">Total Mapped Reports</div>
            <div class="hotspot-value" id="total-mapped-reports">0</div>
        </div>
    </div>
    
    <div class="hotspot-card purple">
        <div class="hotspot-icon"><i class="fas fa-fire-flame-curved"></i></div>
        <div class="hotspot-content">
            <div class="hotspot-label">Top Hotspot Barangay</div>
            <div class="hotspot-value" id="top-hotspot-barangay" style="font-size: 1.125rem;">N/A</div>
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
            <label class="filter-label">Barangay</label>
            <select class="filter-select" id="filter-barangay">
                <option value="">All Barangays</option>
                @foreach(config('santa_cruz_barangays.barangays', []) as $barangayData)
                    <option value="{{ $barangayData['name'] }}">{{ $barangayData['name'] }}</option>
                @endforeach
            </select>
        </div>
        
        <div class="filter-field">
            <label class="filter-label">Violation Type</label>
            <select class="filter-select" id="filter-violation-type">
                <option value="">All Violations</option>
                <option value="Illegal Parking">Illegal Parking</option>
                <option value="Road Obstruction">Road Obstruction</option>
                <option value="Vendor Encroachment">Vendor Encroachment</option>
                <option value="Construction Material">Construction Material</option>
                <option value="Abandoned Vehicle">Abandoned Vehicle</option>
            </select>
        </div>
        
        <div class="filter-field">
            <label class="filter-label">Status</label>
            <select class="filter-select" id="filter-status">
                <option value="">All Statuses</option>
                <option value="Submitted">Submitted</option>
                <option value="For Verification">For Verification</option>
                <option value="Verified">Verified</option>
                <option value="Assigned">Assigned</option>
                <option value="In Progress">In Progress</option>
                <option value="Action Taken">Action Taken</option>
                <option value="Resolved">Resolved</option>
                <option value="Rejected">Rejected</option>
                <option value="Closed">Closed</option>
            </select>
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
                Interactive GIS Map
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
                <div class="legend-label">Santa Cruz Municipal Boundary</div>
            </div>
            <div class="legend-item">
                <div class="legend-symbol report-red"></div>
                <div class="legend-label">Pending Report</div>
            </div>
            <div class="legend-item">
                <div class="legend-symbol report-orange"></div>
                <div class="legend-label">In Progress Report</div>
            </div>
            <div class="legend-item">
                <div class="legend-symbol report-green"></div>
                <div class="legend-label">Resolved Report</div>
            </div>
            <div class="legend-item">
                <div class="legend-symbol office"></div>
                <div class="legend-label">Verified Barangay Office</div>
            </div>
            <div class="legend-item">
                <div class="legend-symbol office" style="background:#F4C542;border-color:#D4A017"></div>
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

<!-- GIS Markers Script (Phase 4D) -->
<script src="{{ asset('js/gis-markers.js') }}"></script>

<script>
    // Default center coordinates for Santa Cruz, Laguna
    const DEFAULT_CENTER = [{{ $defaultCenter['lat'] }}, {{ $defaultCenter['lng'] }}];
    const DEFAULT_ZOOM = 14;
    
    // GeoJSON file URL
    const GEOJSON_URL = "{{ $geojsonUrl }}";
    const GEOJSON_EXISTS = {{ $geojsonExists ? 'true' : 'false' }};

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

    // Boundary style
    function boundaryStyle(feature) {
        return {
            fillColor: 'rgba(244, 197, 66, 0.1)',
            weight: 3,
            opacity: 1,
            color: '#D4A017',
            fillOpacity: 0.2
        };
    }

    // Boundary hover style
    function highlightFeature(e) {
        const layer = e.target;
        layer.setStyle({
            weight: 5,
            color: '#F4C542',
            fillOpacity: 0.4
        });
        layer.bringToFront();
    }

    function resetHighlight(e) {
        geojsonLayer.resetStyle(e.target);
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

    // Bind popup to each feature
    function onEachFeature(feature, layer) {
        const barangayName = getBarangayName(feature.properties);
        
        const popupContent = `
            <div class="barangay-popup">
                <div class="barangay-popup-title">${barangayName}</div>
                <div class="barangay-popup-subtitle">Santa Cruz, Laguna</div>
            </div>
        `;
        
        layer.bindPopup(popupContent);

        layer.on({
            mouseover: highlightFeature,
            mouseout: resetHighlight
        });
    }

    // Load GeoJSON
    let geojsonLayer;
    window.geojsonLayer = null; // Make available globally for marker filtering

    if (GEOJSON_EXISTS) {
        document.getElementById('loading').style.display = 'flex';

        fetch(GEOJSON_URL)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to load GeoJSON');
                }
                return response.json();
            })
            .then(data => {
                document.getElementById('loading').style.display = 'none';

                // Keep ALL polygons in MultiPolygon - show complete Santa Cruz including detached areas
                console.log('🗺️ Loading complete Santa Cruz boundary (all areas)');
                if (data.features[0].geometry.type === 'MultiPolygon') {
                    console.log('📍 Santa Cruz has ' + data.features[0].geometry.coordinates.length + ' polygon area(s)');
                }

                // Add GeoJSON layer
                geojsonLayer = L.geoJSON(data, {
                    style: boundaryStyle,
                    onEachFeature: onEachFeature
                }).addTo(map);
                
                // Make available globally for marker filtering
                window.geojsonLayer = geojsonLayer;

                const bounds = geojsonLayer.getBounds();
                
                if (bounds.isValid()) {
                    // Show ALL Santa Cruz areas including detached polygons
                    // Create inverse mask with holes for ALL polygons in the MultiPolygon
                    const santaCruzCoords = data.features[0].geometry.coordinates;
                    const worldBounds = [
                        [90, -180], [90, 180], [-90, 180], [-90, -180], [90, -180]
                    ];
                    
                    let inverseMask;
                    
                    if (data.features[0].geometry.type === 'MultiPolygon') {
                        // Create holes for ALL polygons (including detached areas)
                        const holes = santaCruzCoords.map(polygon => {
                            return polygon[0].map(coord => [coord[1], coord[0]]);
                        });
                        
                        inverseMask = L.polygon([worldBounds, ...holes], {
                            color: '#ffffff',
                            fillColor: '#ffffff',
                            fillOpacity: 0.85,
                            weight: 0,
                            interactive: false
                        }).addTo(map);
                        
                        console.log('✅ Showing ALL ' + santaCruzCoords.length + ' Santa Cruz polygon area(s)');
                    } else {
                        // Single polygon
                        const hole = santaCruzCoords[0].map(coord => [coord[1], coord[0]]);
                        inverseMask = L.polygon([worldBounds, hole], {
                            color: '#ffffff',
                            fillColor: '#ffffff',
                            fillOpacity: 0.85,
                            weight: 0,
                            interactive: false
                        }).addTo(map);
                    }
                    
                    // Fit map to ALL Santa Cruz bounds (including detached areas)
                    map.fitBounds(bounds, { padding: [30, 30], maxZoom: 13 });
                    
                    // Allow panning within expanded bounds
                    map.setMaxBounds(bounds.pad(0.2));
                    
                    console.log('✅ GeoJSON boundaries loaded - Complete Santa Cruz');
                    console.log('🗺️ Map shows all Santa Cruz areas including detached barangays');
                    
                    // PHASE 4D: Initialize markers and clustering
                    initializeGISMarkers(map);
                }
            })
            .catch(error => {
                document.getElementById('loading').style.display = 'none';
                console.error('❌ Error loading GeoJSON:', error);
                
                L.popup()
                    .setLatLng(DEFAULT_CENTER)
                    .setContent('<div style="text-align: center;"><strong style="color: #ef4444;">⚠️ Failed to load boundaries</strong></div>')
                    .openOn(map);
            });
    } else {
        L.popup()
            .setLatLng(DEFAULT_CENTER)
            .setContent('<div style="text-align: center;"><strong style="color: #f59e0b;">📁 No GeoJSON File</strong></div>')
            .openOn(map);
    }
</script>
@endsection
