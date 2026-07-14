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
function loadHotspotSummary() {
    console.log('📊 Loading hotspot summary...');
    
    fetch('/api/gis/hotspots-summary')
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
    document.getElementById('top-hotspot-barangay').textContent = data.top_hotspot_barangay || 'N/A';
    document.getElementById('most-common-violation').textContent = data.most_common_violation_type || 'N/A';
    document.getElementById('most-common-status').textContent = data.most_common_status || 'N/A';
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
function loadReports(map) {
    console.log('📍 Loading violation reports...');
    
    fetch('/api/gis/reports')
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
        // Determine marker color based on status
        const statusColor = STATUS_COLORS[report.status] || '#6b7280';
        
        // Create custom marker icon
        const markerIcon = L.divIcon({
            className: 'custom-report-marker',
            html: '<div style="background: ' + statusColor + '; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.4); opacity: 0.85;"></div>',
            iconSize: [20, 20],
            iconAnchor: [10, 10]
        });
        
        // Create marker
        const marker = L.marker([report.latitude, report.longitude], {
            icon: markerIcon,
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
    const verificationColor = report.verification_status === 'Verified' ? '#10b981' : '#6b7280';
    
    const popupHTML = '<div style="font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; min-width: 280px;">' +
        '<div style="font-size: 1rem; font-weight: 700; color: #333333; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">' +
        report.tracking_id + '</div>' +
        '<div style="background: #f9fafb; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 0.75rem;">' +
        '<div style="display: grid; grid-template-columns: 1fr; gap: 0.5rem;">' +
        '<div><div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">VIOLATION TYPE</div>' +
        '<div style="font-size: 0.875rem; font-weight: 600; color: #333333;">' + report.selected_violation_type + '</div></div>' +
        '<div style="display: flex; gap: 0.5rem;">' +
        '<div style="flex: 1;"><div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">STATUS</div>' +
        '<div style="display: inline-block; padding: 0.25rem 0.5rem; background: ' + statusColor + '; color: white; font-size: 0.75rem; font-weight: 600; border-radius: 0.25rem;">' + report.status + '</div></div>' +
        '<div style="flex: 1;"><div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">VERIFICATION</div>' +
        '<div style="display: inline-block; padding: 0.25rem 0.5rem; background: ' + verificationColor + '; color: white; font-size: 0.75rem; font-weight: 600; border-radius: 0.25rem;">' + report.verification_status + '</div></div>' +
        '</div></div></div>' +
        '<div style="border-top: 2px solid #F4C542; padding-top: 0.75rem; margin-bottom: 0.75rem;">' +
        '<div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">DETECTED BARANGAY</div>' +
        '<div style="font-size: 0.875rem; font-weight: 600; color: #333333; margin-bottom: 0.5rem;">' + (report.effective_barangay || 'Needs Barangay Review') + '</div>' +
        '<div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">RECOMMENDED BARANGAY OFFICE FOR FOLLOW-UP</div>' +
        '<div style="font-size: 0.875rem; font-weight: 600; color: #D4A017;">' + (report.assigned_barangay_office || 'Pending DILG routing') + '</div></div>' +
        '<div style="text-align: center; margin-top: 0.75rem;">' +
        '<a href="/violation-reports" style="display: inline-block; padding: 0.5rem 1rem; background: #F4C542; color: #333333; text-decoration: none; border-radius: 0.5rem; font-weight: 600; font-size: 0.875rem;">View Report Details</a>' +
        '</div></div>';
    
    return popupHTML;
}

/**
 * Show recommendation panel when marker is clicked
 */
function showRecommendationPanel(report) {
    const panel = document.getElementById('recommendation-panel');
    
    if (!panel) return;
    
    // Update panel content
    document.getElementById('rec-tracking-id').textContent = report.tracking_id;
    document.getElementById('rec-detected-barangay').textContent = report.effective_barangay || 'Needs Barangay Review';
    document.getElementById('rec-office-name').textContent = report.assigned_barangay_office || 'Pending DILG routing';
    
    // Find office address
    const office = allOffices.find(o => o.office_name === report.assigned_barangay_office);
    document.getElementById('rec-office-address').textContent = office ? office.address : 'Address not available';
    document.getElementById('rec-office-validation').textContent = office
        ? (office.validation_status === 'Verified' ? 'Researcher-verified coordinate' : 'Provisional coordinate; validate before deployment')
        : 'No office recommendation until barangay routing is complete';
    
    document.getElementById('rec-report-status').textContent = report.status;
    
    // Show panel
    panel.style.display = 'block';
}

/**
 * Close recommendation panel
 */
function closeRecommendationPanel() {
    const panel = document.getElementById('recommendation-panel');
    
    if (!panel) return;
    
    panel.style.display = 'none';
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
    const barangay = document.getElementById('filter-barangay').value;
    const violationType = document.getElementById('filter-violation-type').value;
    const status = document.getElementById('filter-status').value;
    
    console.log('🔍 Applying filters:', { barangay, violationType, status });
    
    // Filter reports
    let filteredReports = allReports;
    
    if (barangay && barangay !== '') {
        filteredReports = filteredReports.filter(r => r.effective_barangay === barangay);
    }
    
    if (violationType && violationType !== '') {
        filteredReports = filteredReports.filter(r => r.selected_violation_type === violationType);
    }
    
    if (status && status !== '') {
        filteredReports = filteredReports.filter(r => r.status === status);
    }
    
    // Remove existing markers
    if (reportMarkersLayer) {
        map.removeLayer(reportMarkersLayer);
    }
    
    // Create new layer with filtered reports
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
    
    // Add filtered markers
    filteredReports.forEach(report => {
        const statusColor = STATUS_COLORS[report.status] || '#6b7280';
        
        const markerIcon = L.divIcon({
            className: 'custom-report-marker',
            html: `<div style="background: ${statusColor}; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.4); opacity: 0.85;"></div>`,
            iconSize: [20, 20],
            iconAnchor: [10, 10]
        });
        
        const marker = L.marker([report.latitude, report.longitude], {
            icon: markerIcon,
            title: report.tracking_id
        });
        
        const popupContent = createReportPopup(report);
        marker.bindPopup(popupContent, { maxWidth: 350 });
        
        marker.on('click', () => {
            showRecommendationPanel(report);
        });
        
        marker.addTo(reportMarkersLayer);
    });
    
    // Add layer to map
    reportMarkersLayer.addTo(map);
    
    // Update visible count
    updateVisibleCount(filteredReports.length);
    
    console.log(`✅ Showing ${filteredReports.length} filtered reports`);
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
    
    // Redisplay all reports
    displayReportMarkers(map);
    
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
