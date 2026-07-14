/**
 * PHASE 4D.1 - Individual Report Location Map
 * 
 * This script displays a mini Leaflet map for a specific violation report.
 * Shows:
 * - Report marker at GPS location
 * - Detected barangay boundary (highlighted)
 * - Assigned barangay office marker
 * - Report details popup
 * 
 * Dependencies:
 * - Leaflet.js (from Phase 4B)
 * - boundary.geojson (from Phase 4B)
 */

/**
 * Initialize the mini report location map
 * 
 * @param {Object} reportData - Report information
 * @param {string} geojsonUrl - URL to boundary.geojson file
 */
function initializeReportLocationMap(reportData, geojsonUrl) {
    console.log('🗺️ Initializing report location map...');
    
    // Validate GPS coordinates
    if (!reportData.latitude || !reportData.longitude) {
        console.warn('⚠️ GPS coordinates missing');
        return;
    }
    
    const reportLatLng = [reportData.latitude, reportData.longitude];
    
    // Initialize map centered on report location
    const map = L.map('report-location-map', {
        center: reportLatLng,
        zoom: 15,
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
    
    // Status color mapping
    const STATUS_COLORS = {
        'Submitted': '#3b82f6',
        'For Verification': '#f59e0b',
        'Verified': '#10b981',
        'Assigned': '#6366f1',
        'In Progress': '#a855f7',
        'Action Taken': '#ec4899',
        'Resolved': '#10b981',
        'Rejected': '#ef4444',
        'Closed': '#6b7280'
    };
    
    const markerColor = STATUS_COLORS[reportData.status] || '#6b7280';
    
    // Create report marker icon
    const reportIcon = L.divIcon({
        className: 'report-location-marker',
        html: '<div style="background: ' + markerColor + '; width: 28px; height: 28px; border-radius: 50%; border: 3px solid white; box-shadow: 0 3px 10px rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">📍</div>',
        iconSize: [28, 28],
        iconAnchor: [14, 14]
    });
    
    // Add report marker
    const reportMarker = L.marker(reportLatLng, {
        icon: reportIcon,
        title: reportData.tracking_id
    }).addTo(map);
    
    // Create report popup content
    const popupContent = '<div style="font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; min-width: 200px;">' +
        '<div style="font-size: 1rem; font-weight: 700; color: #333333; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">' +
        '<span style="font-size: 1.125rem;">📋</span>' + reportData.tracking_id + '</div>' +
        '<div style="background: #f9fafb; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 0.5rem;">' +
        '<div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">VIOLATION TYPE</div>' +
        '<div style="font-size: 0.875rem; font-weight: 600; color: #333333; margin-bottom: 0.5rem;">' + reportData.violation_type + '</div>' +
        '<div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">STATUS</div>' +
        '<div style="display: inline-block; padding: 0.25rem 0.5rem; background: ' + markerColor + '; color: white; font-size: 0.75rem; font-weight: 600; border-radius: 0.25rem; margin-bottom: 0.5rem;">' + reportData.status + '</div>' +
        '<div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">DETECTED BARANGAY</div>' +
        '<div style="font-size: 0.875rem; font-weight: 600; color: #333333; margin-bottom: 0.5rem;">' + reportData.detected_barangay + '</div>' +
        '<div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">LOCATION CONTEXT</div>' +
        '<div style="font-size: 0.875rem; color: #333333;">' + (reportData.location_context || 'N/A') + '</div></div>' +
        '</div>';
    
    reportMarker.bindPopup(popupContent, { maxWidth: 300 }).openPopup();
    
    // Load barangay boundaries
    loadBarangayBoundaries(map, geojsonUrl, reportData.detected_barangay);
    
    // Load barangay office marker if coordinates available
    if (reportData.office_latitude && reportData.office_longitude) {
        addBarangayOfficeMarker(map, reportData);
    }
    
    console.log('✅ Report location map initialized');
}

/**
 * Load and display barangay boundaries from GeoJSON
 */
function loadBarangayBoundaries(map, geojsonUrl, detectedBarangay) {
    console.log('🗺️ Loading barangay boundaries...');
    
    fetch(geojsonUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load GeoJSON');
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ GeoJSON loaded');
            
            // Add GeoJSON layer with conditional styling
            L.geoJSON(data, {
                style: function(feature) {
                    const barangayName = getBarangayName(feature.properties);
                    
                    // Highlight detected barangay
                    if (barangayName && barangayName.toLowerCase() === detectedBarangay.toLowerCase()) {
                        return {
                            fillColor: '#F4C542',
                            weight: 4,
                            opacity: 1,
                            color: '#D4A017',
                            fillOpacity: 0.4
                        };
                    }
                    
                    // Default boundary style
                    return {
                        fillColor: 'rgba(244, 197, 66, 0.1)',
                        weight: 2,
                        opacity: 1,
                        color: '#D4A017',
                        fillOpacity: 0.15
                    };
                },
                onEachFeature: function(feature, layer) {
                    const barangayName = getBarangayName(feature.properties);
                    
                    const popupContent = '<div style="font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;">' +
                        '<div style="font-size: 0.9375rem; font-weight: 700; color: #333333;">' + barangayName + '</div>' +
                        '<div style="font-size: 0.75rem; color: #6b7280;">Santa Cruz, Laguna</div>' +
                        '</div>';
                    
                    layer.bindPopup(popupContent);
                }
            }).addTo(map);
            
            console.log('✅ Barangay boundaries displayed');
        })
        .catch(error => {
            console.error('❌ Error loading boundaries:', error);
        });
}

/**
 * Detect barangay name from GeoJSON properties
 */
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

/**
 * Add barangay office marker
 */
function addBarangayOfficeMarker(map, reportData) {
    console.log('🏢 Adding barangay office marker...');
    
    const officeLatLng = [reportData.office_latitude, reportData.office_longitude];
    
    // Create office icon (gold/yellow)
    const officeIcon = L.divIcon({
        className: 'office-location-marker',
        html: '<div style="background: #F4C542; width: 24px; height: 24px; border-radius: 50%; border: 3px solid #D4A017; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; color: #D4A017; font-weight: bold;">🏢</div>',
        iconSize: [24, 24],
        iconAnchor: [12, 12]
    });
    
    // Add office marker
    const officeMarker = L.marker(officeLatLng, {
        icon: officeIcon,
        title: reportData.assigned_barangay_office
    }).addTo(map);
    
    // Create office popup content
    const popupContent = '<div style="font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; min-width: 200px;">' +
        '<div style="font-size: 1rem; font-weight: 700; color: #333333; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">' +
        '<span style="font-size: 1.125rem;">🏢</span>' + reportData.assigned_barangay_office + '</div>' +
        '<div style="background: #fef3c7; padding: 0.75rem; border-radius: 0.5rem; border-left: 3px solid #F4C542;">' +
        '<div style="font-size: 0.7rem; color: #92400e; text-transform: uppercase; margin-bottom: 0.25rem;">RECOMMENDED BARANGAY OFFICE FOR FOLLOW-UP</div>' +
        '<div style="font-size: 0.875rem; color: #333333; margin-top: 0.5rem;">' + reportData.detected_barangay + ', Santa Cruz, Laguna</div>' +
        '</div></div>';
    
    officeMarker.bindPopup(popupContent, { maxWidth: 300 });
    
    console.log('✅ Barangay office marker added');
}

// Export for global access
window.initializeReportLocationMap = initializeReportLocationMap;
