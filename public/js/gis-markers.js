/**
 * PHASE 4D - GIS Report Markers, Clustering, and Hotspots
 * 
 * This script handles:
 * - Loading violation report markers with GPS coordinates
 * - Marker clustering for better readability
 * - Barangay office markers
 * - Filter functionality (barangay, violation type, status)
 * - Hotspot summary statistics
 * - Barangay office recommendation panel
 * 
 * Dependencies:
 * - Leaflet.js (already loaded)
 * - Leaflet.markercluster (loaded via CDN in view)
 */

// Global variables
let reportMarkersLayer = null;
let officeMarkersLayer = null;
let allReports = [];
let allOffices = [];

function escapeHtml(value) {
    const element = document.createElement('div');
    element.textContent = value ?? '';
    return element.innerHTML;
}

function buildFilterQuery() {
    const params = new URLSearchParams();
    const values = {
        dataset: document.getElementById('filter-dataset')?.value || 'operational',
        barangay: document.getElementById('filter-barangay')?.value || '',
        violation_type: document.getElementById('filter-violation-type')?.value || '',
        status: document.getElementById('filter-status')?.value || '',
        date_from: document.getElementById('filter-date-from')?.value || '',
        date_to: document.getElementById('filter-date-to')?.value || ''
    };

    Object.entries(values).forEach(([key, value]) => {
        if (value) {
            params.set(key, value);
        }
    });

    const query = params.toString();
    return query ? `?${query}` : '';
}

// Status color mapping (matching DaisyUI badge colors)
const STATUS_COLORS = {
    'Submitted': '#3b82f6',          // Blue
    'For Verification': '#f59e0b',   // Orange
    'Verified': '#10b981',           // Green
    'Assigned': '#6366f1',           // Indigo
    'In Progress': '#a855f7',        // Purple
    'Action Taken': '#ec4899',       // Pink
    'Resolved': '#10b981',           // Green
    'Rejected': '#ef4444',           // Red
    'Closed': '#6b7280'              // Gray
};

// The marker fill shows workflow status. Its ring and symbol show whether the
// report is eligible for official statistics or why it is operational-only.
const REPORT_STATE_STYLES = {
    verified_valid: { background: '#10b981', border: '#047857', symbol: '<i class="fas fa-check" aria-hidden="true"></i>', label: 'Staff-verified valid violation' },
    ai_pending: { background: '#3b82f6', border: '#1d4ed8', symbol: '<i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i>', label: 'AI analysis pending' },
    pending_verification: { background: '#f59e0b', border: '#b45309', symbol: '<i class="fas fa-clock" aria-hidden="true"></i>', label: 'Awaiting staff verification' },
    rejected: { background: '#ef4444', border: '#b91c1c', symbol: '<i class="fas fa-xmark" aria-hidden="true"></i>', label: 'Rejected or invalid report' },
    duplicate: { background: '#8b5cf6', border: '#6d28d9', symbol: '<i class="fas fa-copy" aria-hidden="true"></i>', label: 'Duplicate report' },
    outside_jurisdiction: { background: '#64748b', border: '#475569', symbol: '<i class="fas fa-file-lines" aria-hidden="true"></i>', label: 'Outside supported jurisdiction' },
    test_data: { background: '#94a3b8', border: '#64748b', symbol: '<i class="fas fa-file-lines" aria-hidden="true"></i>', label: 'Test data' }
};

// Violation type color mapping
const VIOLATION_COLORS = {
    'Illegal Parking': '#ef4444',       // Red
    'Road Obstruction': '#f59e0b',      // Orange
    'Vendor Encroachment': '#eab308',   // Yellow
    'Construction Material': '#84cc16', // Lime
    'Abandoned Vehicle': '#6b7280'      // Gray
};

/**
 * Initialize GIS markers and clustering
 */
function initializeGISMarkers(map) {
    console.log('🗺️ Initializing GIS markers...');
    
    // Check if MarkerCluster plugin is available
    if (typeof L.markerClusterGroup === 'undefined') {
        console.warn('⚠️ Leaflet.markercluster plugin not loaded. Falling back to normal markers.');
    }
    
    // Load hotspot summary
    loadHotspotSummary();
    
    // Load barangay offices
    loadBarangayOffices(map);
    
    // Load reports
    loadReports(map);
    
    // Setup filter handlers
    setupFilters(map);
}

/**
 * Load hotspot summary statistics
 */
function loadHotspotSummary(query = '') {
    console.log('📊 Loading hotspot summary...');
    
    return fetch(`/api/gis/hotspots-summary${query}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                updateHotspotCards(result.data);
                console.log('✅ Hotspot summary loaded');
            } else {
                console.error('❌ Failed to load hotspot summary:', result.message);
            }
        })
        .catch(error => {
            console.error('❌ Error loading hotspot summary:', error);
        });
}

/**
 * Update hotspot summary cards
 */
function updateHotspotCards(data) {
    // Update card values
    document.getElementById('total-mapped-reports').textContent = data.total_mapped_reports || 0;
    document.getElementById('top-hotspot-barangay').textContent = data.top_hotspot_barangay
        || window.CIVICLEAR_GIS_CONTEXT?.assignedBarangay
        || 'N/A';
    document.getElementById('most-common-violation').textContent = data.most_common_violation_type || 'N/A';
    document.getElementById('most-common-status').textContent = data.most_common_status || 'N/A';

    const dataset = document.getElementById('filter-dataset')?.value || 'operational';
    const label = document.getElementById('mapped-reports-label');
    if (label) {
        label.textContent = dataset === 'official'
            ? 'Official Verified Reports'
            : 'Operational Mapped Reports';
    }
}

function createReportMarker(report) {
    const stateStyle = REPORT_STATE_STYLES[report.operational_state]
        || REPORT_STATE_STYLES.pending_verification;

    return L.divIcon({
        className: 'custom-report-marker',
        html: '<div aria-label="' + escapeHtml(report.operational_state_label || stateStyle.label) + '" ' +
            'style="background:' + stateStyle.background + ';width:24px;height:24px;border-radius:50%;' +
            'border:3px solid ' + stateStyle.border + ';box-shadow:0 2px 7px rgba(15,23,42,.4);' +
            'color:white;display:flex;align-items:center;justify-content:center;font-size:10px;' +
            'font-weight:900;line-height:1;opacity:.95">' + stateStyle.symbol + '</div>',
        iconSize: [24, 24],
        iconAnchor: [12, 12]
    });
}

/**
 * Load barangay offices and add markers
 */
function loadBarangayOffices(map) {
    console.log('🏢 Loading barangay offices...');
    
    fetch('/api/gis/barangay-offices')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                allOffices = result.data;
                displayOfficeMarkers(map);
                console.log(`✅ Loaded ${allOffices.length} barangay offices`);
            } else {
                console.error('❌ Failed to load barangay offices:', result.message);
            }
        })
        .catch(error => {
            console.error('❌ Error loading barangay offices:', error);
        });
}

/**
 * Display barangay office markers
 */
function displayOfficeMarkers(map) {
    // Remove existing layer if any
    if (officeMarkersLayer) {
        map.removeLayer(officeMarkersLayer);
    }
    
    // Create layer group for office markers
    officeMarkersLayer = L.layerGroup();
    
    allOffices.forEach(office => {
        const isVerified = office.validation_status === 'Verified';
        const markerFill = isVerified ? '#10b981' : '#F4C542';
        const markerBorder = isVerified ? '#047857' : '#D4A017';

        // Verified offices are green; provisional coordinates remain gold.
        const officeIcon = L.divIcon({
            className: 'custom-office-marker',
            html: '<div style="background:' + markerFill + ';width:24px;height:24px;border-radius:50%;border:3px solid ' + markerBorder + ';box-shadow:0 3px 10px rgba(15,23,42,.28)"></div>',
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });
        
        // Create marker
        const marker = L.marker([office.latitude, office.longitude], {
            icon: officeIcon,
            title: office.office_name
        });
        
        // Create popup content
        const popupContent = '<div style="font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; min-width: 200px;">' +
            '<div style="font-size: 1rem; font-weight: 700; color: #333333; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">' +
            office.office_name + '</div>' +
            '<div style="padding: 0.5rem 0; border-top: 2px solid #F4C542; border-bottom: 2px solid #F4C542; margin-bottom: 0.5rem;">' +
            '<div style="font-size: 0.75rem; color: #6b7280; margin-bottom: 0.25rem;">BARANGAY</div>' +
            '<div style="font-size: 0.875rem; font-weight: 600; color: #333333;">' + office.barangay + '</div></div>' +
            '<div style="font-size: 0.75rem; color: #6b7280; margin-bottom: 0.25rem;">ADDRESS</div>' +
            '<div style="font-size: 0.875rem; color: #333333; margin-bottom: 0.75rem;">' + office.address + '</div>' +
            '<div style="display:inline-flex;padding:.25rem .5rem;border-radius:999px;background:' + (isVerified ? '#d1fae5' : '#fef3c7') + ';color:' + (isVerified ? '#065f46' : '#92400e') + ';font-size:.7rem;font-weight:700;margin-bottom:.65rem">' + (isVerified ? 'Researcher verified' : 'Provisional coordinate') + '</div>' +
            '<div style="background:#f8fafc;padding:.5rem;border-radius:.375rem;border-left:3px solid ' + markerBorder + '">' +
            '<div style="font-size:.75rem;color:#475569;font-weight:500">Recommended follow-up office only; it does not determine jurisdiction.</div>' +
            '</div></div>';
        
        marker.bindPopup(popupContent, { maxWidth: 300 });
        marker.addTo(officeMarkersLayer);
    });
    
    // Add layer to map
    officeMarkersLayer.addTo(map);
}

/**
 * Load violation reports and add markers
 * Shows ALL reports with GPS coordinates (including detached Santa Cruz areas)
 */
function loadReports(map, query = '') {
    console.log('📍 Loading violation reports...');
    
    return fetch(`/api/gis/reports${query}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                allReports = result.data;
                displayReportMarkers(map);
                console.log(`✅ Loaded ${allReports.length} violation reports (all Santa Cruz areas)`);
            } else {
                console.error('❌ Failed to load reports:', result.message);
            }
        })
        .catch(error => {
            console.error('❌ Error loading reports:', error);
        });
}

/**
 * Display report markers with clustering
 */
function displayReportMarkers(map) {
    // Remove existing layer if any
    if (reportMarkersLayer) {
        map.removeLayer(reportMarkersLayer);
    }
    
    // Create marker cluster group or regular layer group
    if (typeof L.markerClusterGroup !== 'undefined') {
        reportMarkersLayer = L.markerClusterGroup({
            maxClusterRadius: 50,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            zoomToBoundsOnClick: true
        });
    } else {
        reportMarkersLayer = L.layerGroup();
    }
    
    allReports.forEach(report => {
        // Create marker
        const marker = L.marker([report.latitude, report.longitude], {
            icon: createReportMarker(report),
            title: report.tracking_id
        });
        
        // Create popup content
        const popupContent = createReportPopup(report);
        marker.bindPopup(popupContent, { maxWidth: 350 });
        
        // Add click event to show recommendation panel
        marker.on('click', () => {
            showRecommendationPanel(report);
        });
        
        marker.addTo(reportMarkersLayer);
    });
    
    // Add layer to map
    reportMarkersLayer.addTo(map);
    
    // Update visible count
    updateVisibleCount(allReports.length);
}

/**
 * Create report popup content
 */
function createReportPopup(report) {
    const statusColor = STATUS_COLORS[report.status] || '#6b7280';
    const stateStyle = REPORT_STATE_STYLES[report.operational_state]
        || REPORT_STATE_STYLES.pending_verification;
    const trackingId = escapeHtml(report.tracking_id || 'Report');
    const violationType = escapeHtml(
        report.official_violation_type
        || report.selected_violation_type
        || 'Awaiting Staff Classification'
    );
    const status = escapeHtml(report.status || 'Unknown');
    const verificationStatus = escapeHtml(report.operational_state_label || stateStyle.label);
    const effectiveBarangay = escapeHtml(report.effective_barangay || 'Needs Barangay Review');
    const officeName = escapeHtml(report.assigned_barangay_office || 'Pending DILG routing');
    const detailsUrl = escapeHtml(report.details_url || '#');
    
    const popupHTML = '<div style="font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; min-width: 280px;">' +
        '<div style="font-size: 1rem; font-weight: 700; color: #333333; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">' +
        trackingId + '</div>' +
        '<div style="background: #f9fafb; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 0.75rem;">' +
        '<div style="display: grid; grid-template-columns: 1fr; gap: 0.5rem;">' +
        '<div><div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">VIOLATION TYPE</div>' +
        '<div style="font-size: 0.875rem; font-weight: 600; color: #333333;">' + violationType + '</div></div>' +
        '<div style="display: flex; gap: 0.5rem;">' +
        '<div style="flex: 1;"><div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">STATUS</div>' +
        '<div style="display: inline-block; padding: 0.25rem 0.5rem; background: ' + statusColor + '; color: white; font-size: 0.75rem; font-weight: 600; border-radius: 0.25rem;">' + status + '</div></div>' +
        '<div style="flex: 1;"><div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">VERIFICATION</div>' +
        '<div style="display: inline-block; padding: 0.25rem 0.5rem; background: ' + stateStyle.border + '; color: white; font-size: 0.75rem; font-weight: 600; border-radius: 0.25rem;">' + verificationStatus + '</div></div>' +
        '</div></div></div>' +
        '<div style="border-top: 2px solid #F4C542; padding-top: 0.75rem; margin-bottom: 0.75rem;">' +
        '<div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">DETECTED BARANGAY</div>' +
        '<div style="font-size: 0.875rem; font-weight: 600; color: #333333; margin-bottom: 0.5rem;">' + effectiveBarangay + '</div>' +
        '<div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">RECOMMENDED BARANGAY OFFICE FOR FOLLOW-UP</div>' +
        '<div style="font-size: 0.875rem; font-weight: 600; color: #174EA6;">' + officeName + '</div></div>' +
        '<div style="text-align: center; margin-top: 0.75rem;">' +
        '<a href="' + detailsUrl + '" style="display: inline-block; padding: 0.5rem 1rem; background: #2F80ED; color: white; text-decoration: none; border-radius: 0.5rem; font-weight: 600; font-size: 0.875rem;">View Report Details</a>' +
        '</div></div>';
    
    return popupHTML;
}

/**
 * Show recommendation panel when marker is clicked
 */
function showRecommendationPanel(report) {
    const panel = document.getElementById('recommendation-panel');
    const emptyState = document.getElementById('report-panel-empty');
    const content = document.getElementById('report-panel-content');
    
    if (!panel) return;
    
    // Update panel content
    document.getElementById('rec-tracking-id').textContent = report.tracking_id;
    document.getElementById('rec-detected-barangay').textContent = report.effective_barangay || 'Needs Barangay Review';
    document.getElementById('rec-office-name').textContent = report.assigned_barangay_office || 'Pending DILG routing';
    document.getElementById('rec-violation-type').textContent = report.official_violation_type
        || report.selected_violation_type
        || 'Awaiting staff classification';
    document.getElementById('rec-validation-state').textContent = report.operational_state_label
        || 'Awaiting staff verification';
    document.getElementById('rec-gps').textContent = Number.isFinite(Number(report.latitude))
        && Number.isFinite(Number(report.longitude))
        ? `${Number(report.latitude).toFixed(6)}, ${Number(report.longitude).toFixed(6)}`
        : 'Location unavailable';
    
    // Find office address
    const office = allOffices.find(o => o.office_name === report.assigned_barangay_office);
    document.getElementById('rec-office-address').textContent = office ? office.address : 'Address not available';
    document.getElementById('rec-office-validation').textContent = office
        ? (office.validation_status === 'Verified' ? 'Researcher-verified coordinate' : 'Provisional coordinate; validate before deployment')
        : 'No office recommendation until barangay routing is complete';
    
    document.getElementById('rec-report-status').textContent = report.status;
    
    panel.style.display = 'flex';
    if (emptyState) emptyState.hidden = true;
    if (content) content.hidden = false;
}

/**
 * Close recommendation panel
 */
function closeRecommendationPanel() {
    const panel = document.getElementById('recommendation-panel');
    const emptyState = document.getElementById('report-panel-empty');
    const content = document.getElementById('report-panel-content');
    
    if (!panel) return;

    panel.style.display = 'flex';
    if (emptyState) emptyState.hidden = false;
    if (content) content.hidden = true;
}

/**
 * Setup filter handlers
 */
function setupFilters(map) {
    const applyBtn = document.getElementById('apply-filters-btn');
    const resetBtn = document.getElementById('reset-filters-btn');
    
    if (applyBtn) {
        applyBtn.addEventListener('click', () => applyFilters(map));
    }
    
    if (resetBtn) {
        resetBtn.addEventListener('click', () => resetFilters(map));
    }
}

/**
 * Apply filters to report markers
 */
function applyFilters(map) {
    const query = buildFilterQuery();

    console.log('Applying server-authorized GIS filters:', query);
    Promise.all([loadReports(map, query), loadHotspotSummary(query)]);
}

/**
 * Reset filters and show all reports
 */
function resetFilters(map) {
    console.log('🔄 Resetting filters...');
    
    // Reset dropdowns
    document.getElementById('filter-barangay').value = '';
    document.getElementById('filter-violation-type').value = '';
    document.getElementById('filter-status').value = '';
    document.getElementById('filter-date-from').value = '';
    document.getElementById('filter-date-to').value = '';
    document.getElementById('filter-dataset').value = 'operational';

    document.getElementById('filter-barangay').value = window.CIVICLEAR_GIS_CONTEXT?.assignedBarangay || '';

    const query = buildFilterQuery();
    Promise.all([loadReports(map, query), loadHotspotSummary(query)]);
    
    console.log('✅ All filters reset');
}

/**
 * Update visible marker count
 */
function updateVisibleCount(count) {
    const countElement = document.getElementById('visible-markers-count');
    if (countElement) {
        countElement.textContent = count;
    }
}

// Export functions for global access
window.initializeGISMarkers = initializeGISMarkers;
window.closeRecommendationPanel = closeRecommendationPanel;
