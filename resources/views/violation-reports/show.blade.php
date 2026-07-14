@extends($isBarangayView ? 'layouts.barangay-app' : 'layouts.dilg-app')

@section('title', 'View Report - DILG-RC')

@section('content')
<style>
    .detail-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .detail-card {
        background: white;
        padding: 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 1rem;
        margin-bottom: 1.5rem;
        border-bottom: 2px solid var(--dilg-yellow);
    }

    .detail-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--dilg-dark-gray);
    }

    .detail-row {
        display: grid;
        grid-template-columns: 150px 1fr;
        gap: 1rem;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f3f4f6;
    }

    .detail-label {
        font-weight: 500;
        color: #6b7280;
        font-size: 0.875rem;
    }

    .detail-value {
        color: var(--dilg-dark-gray);
        font-size: 0.875rem;
    }

    .photo-preview {
        width: 100%;
        max-height: 400px;
        object-fit: cover;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }

    .no-photo {
        background: #f3f4f6;
        padding: 3rem;
        text-align: center;
        border-radius: 0.5rem;
        color: #9ca3af;
        font-size: 0.875rem;
    }

    .badge {
        display: inline-block;
        padding: 0.375rem 0.875rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .badge-submitted { background: #dbeafe; color: #1e40af; }
    .badge-forverification { background: #fef3c7; color: #92400e; }
    .badge-verified { background: #d1fae5; color: #065f46; }
    .badge-assigned { background: #e0e7ff; color: #3730a3; }
    .badge-inprogress { background: #fce7f3; color: #9f1239; }
    .badge-actiontaken { background: #ddd6fe; color: #5b21b6; }
    .badge-resolved { background: #bbf7d0; color: #14532d; }
    .badge-rejected { background: #fee2e2; color: #991b1b; }
    .badge-closed { background: #e5e7eb; color: #374151; }

    .gps-card {
        background: #dbeafe;
        border-left: 4px solid #3b82f6;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-top: 1rem;
    }

    .gps-title {
        font-weight: 600;
        color: #1e40af;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }

    .gps-coords {
        font-family: 'Courier New', monospace;
        color: #1e40af;
        font-size: 0.875rem;
    }

    .timeline {
        margin-top: 1.5rem;
    }

    .timeline-item {
        padding: 0.75rem;
        border-left: 3px solid var(--dilg-yellow);
        margin-left: 0.5rem;
        margin-bottom: 0.75rem;
        background: #fef3c7;
        border-radius: 0.25rem;
    }

    .timeline-status {
        font-weight: 600;
        color: var(--dilg-dark-gray);
        font-size: 0.875rem;
    }

    .timeline-date {
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }

    .action-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .btn-primary {
        background: var(--dilg-dark-gold);
        color: white !important;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
        font-size: 0.875rem;
    }

    .btn-primary:hover {
        background: var(--dilg-yellow);
        color: var(--dilg-dark-gray) !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(212, 160, 23, 0.3);
    }

    .btn-secondary {
        background: #6b7280;
        color: white !important;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
        font-size: 0.875rem;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }

    .full-width-card {
        grid-column: 1 / -1;
    }

    .map-placeholder {
        background: #f3f4f6;
        padding: 2rem;
        text-align: center;
        border-radius: 0.5rem;
        color: #6b7280;
        border: 2px dashed #d1d5db;
    }

    @media (max-width: 768px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="detail-container">
    <div class="page-header">
        <h1 class="page-title">🔍 View Violation Report</h1>
        <p class="page-subtitle">{{ $violationReport->report_id }}</p>
    </div>

    <div class="detail-grid">
        <!-- Main Report Details -->
        <div>
            <div class="detail-card">
                <div class="detail-header">
                    <h3 class="detail-title">Report Information</h3>
                    <x-status-badge :status="$violationReport->status" size="lg" />
                </div>

                <div class="flex flex-wrap gap-2 mb-4">
                    @if($violationReport->municipality_validated)
                        <span class="badge badge-success">Inside Santa Cruz</span>
                    @else
                        <span class="badge badge-error">Outside Coverage</span>
                    @endif
                    @if($violationReport->barangay_detection_status === 'auto_detected')
                        <span class="badge badge-info">Barangay Auto-Detected</span>
                    @elseif($violationReport->manually_assigned_barangay)
                        <span class="badge badge-primary">Manually Routed</span>
                    @elseif($violationReport->needs_manual_barangay_review)
                        <x-status-badge status="Needs Barangay Review" />
                    @endif
                </div>

                <div class="detail-row">
                    <div class="detail-label">Report ID:</div>
                    <div class="detail-value"><strong>{{ $violationReport->report_id }}</strong></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Violation Type:</div>
                    <div class="detail-value"><strong>{{ $violationReport->selected_violation_type }}</strong></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Description:</div>
                    <div class="detail-value">{{ $violationReport->description }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Date Submitted:</div>
                    <div class="detail-value">{{ $violationReport->date_submitted ? $violationReport->date_submitted->format('F d, Y') : 'N/A' }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Timestamp:</div>
                    <div class="detail-value">{{ $violationReport->timestamp ? $violationReport->timestamp->format('M d, Y h:i A') : 'N/A' }}</div>
                </div>

                <!-- GPS Information -->
                <div class="gps-card">
                    <div class="gps-title">📍 GPS Location Information</div>
                    @if($violationReport->latitude && $violationReport->longitude)
                        <div class="gps-coords">
                            <strong>Latitude:</strong> {{ $violationReport->latitude }}<br>
                            <strong>Longitude:</strong> {{ $violationReport->longitude }}<br>
                            <strong>Accuracy:</strong> {{ $violationReport->gps_accuracy ?? 'N/A' }} meters
                        </div>
                    @else
                        <div class="gps-coords">GPS coordinates not available</div>
                    @endif
                </div>
            </div>

            <!-- Photo Evidence -->
            <div class="detail-card" style="margin-top: 1.5rem;">
                <div class="detail-header">
                    <h3 class="detail-title">📷 Photo Evidence</h3>
                </div>
                @if($violationReport->image_path)
                    <img src="{{ asset('storage/' . $violationReport->image_path) }}" alt="Violation Photo" class="photo-preview">
                @else
                    <div class="no-photo">
                        📷 No photo attached
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <!-- Barangay Assignment -->
            <div class="detail-card">
                <div class="detail-header">
                    <h3 class="detail-title">📌 Barangay Assignment</h3>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Effective Barangay:</div>
                    <div class="detail-value"><strong>{{ $violationReport->effective_barangay ?? 'Unassigned' }}</strong></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Routing Method:</div>
                    <div class="detail-value">{{ $violationReport->manually_assigned_barangay ? 'Temporary DILG Routing' : ($violationReport->barangay_detection_status === 'auto_detected' ? 'Barangay Polygon' : 'Awaiting Review') }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Barangay Office:</div>
                    <div class="detail-value">{{ $violationReport->assigned_barangay_office }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Location Context:</div>
                    <div class="detail-value">{{ $violationReport->location_context ?? 'N/A' }}</div>
                </div>
            </div>

            <!-- Response Information -->
            <div class="detail-card" style="margin-top: 1.5rem;">
                <div class="detail-header">
                    <h3 class="detail-title">👤 Response Details</h3>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Assigned Personnel:</div>
                    <div class="detail-value">{{ $violationReport->assigned_personnel ?? 'Not assigned yet' }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Action Taken:</div>
                    <div class="detail-value">{{ $violationReport->action_taken ?? 'No action yet' }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Remarks:</div>
                    <div class="detail-value">{{ $violationReport->remarks ?? 'No remarks' }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Last Updated:</div>
                    <div class="detail-value">{{ $violationReport->date_updated ? $violationReport->date_updated->format('M d, Y') : 'Not updated' }}</div>
                </div>
            </div>

            <!-- Status Timeline -->
            <div class="detail-card" style="margin-top: 1.5rem;">
                <div class="detail-header">
                    <h3 class="detail-title">📅 Status Timeline</h3>
                    <p style="color: #6b7280; font-size: 0.75rem; margin-top: 0.5rem;">Complete history of all status updates</p>
                </div>
                <x-status-timeline 
                    :timelines="$violationReport->timelines" 
                    :currentStatus="$violationReport->status" 
                />
            </div>
        </div>
    </div>

    <!-- Location Map -->
    <div class="detail-card full-width-card">
        <div class="detail-header">
            <h3 class="detail-title">🗺️ Location Map</h3>
            <p style="color: #6b7280; font-size: 0.75rem; margin-top: 0.5rem;">GPS-based road-clearing report location</p>
        </div>
        
        @if($violationReport->latitude && $violationReport->longitude)
            <!-- Map Container -->
            <div id="report-location-map" style="width: 100%; height: 400px; border-radius: 0.5rem; margin-bottom: 1rem;"></div>
            
            <!-- GPS Info Row -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div style="background: #f9fafb; padding: 0.75rem; border-radius: 0.5rem; border-left: 3px solid #3b82f6;">
                    <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">GPS COORDINATES</div>
                    <div style="font-family: 'Courier New', monospace; color: #333333; font-size: 0.875rem; font-weight: 600;">
                        {{ $violationReport->latitude }}, {{ $violationReport->longitude }}
                    </div>
                </div>
                
                <div style="background: #f9fafb; padding: 0.75rem; border-radius: 0.5rem; border-left: 3px solid #10b981;">
                    <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">EFFECTIVE BARANGAY</div>
                    <div style="color: #333333; font-size: 0.875rem; font-weight: 600;">
                        {{ $violationReport->effective_barangay ?? 'Needs Barangay Review' }}
                    </div>
                </div>
                
                <div style="background: #f9fafb; padding: 0.75rem; border-radius: 0.5rem; border-left: 3px solid #f59e0b;">
                    <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">LOCATION CONTEXT</div>
                    <div style="color: #333333; font-size: 0.875rem; font-weight: 600;">
                        {{ $violationReport->location_context ?? 'N/A' }}
                    </div>
                </div>
                
                @if($violationReport->gps_accuracy)
                <div style="background: #f9fafb; padding: 0.75rem; border-radius: 0.5rem; border-left: 3px solid #6b7280;">
                    <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.25rem;">GPS ACCURACY</div>
                    <div style="color: #333333; font-size: 0.875rem; font-weight: 600;">
                        {{ $violationReport->gps_accuracy }} meters
                    </div>
                </div>
                @endif
            </div>
            
            <!-- Recommended Office Note -->
            @if($violationReport->assigned_barangay_office)
            <div style="background: #fef3c7; padding: 1rem; border-radius: 0.5rem; border-left: 4px solid #F4C542; margin-top: 1rem;">
                <div style="font-size: 0.7rem; color: #92400e; text-transform: uppercase; margin-bottom: 0.5rem; font-weight: 600;">
                    📍 RECOMMENDED BARANGAY OFFICE FOR FOLLOW-UP
                </div>
                <div style="color: #333333; font-size: 0.9375rem; font-weight: 600;">
                    {{ $violationReport->assigned_barangay_office }}
                </div>
                <div style="color: #92400e; font-size: 0.8125rem; margin-top: 0.25rem;">
                    {{ $violationReport->effective_barangay }}, Santa Cruz, Laguna
                </div>
            </div>
            @endif
            
            <!-- Leaflet CSS -->
            <link rel="stylesheet" href="{{ asset('css/leaflet.css') }}" />
            
            <!-- Leaflet JS -->
            <script src="{{ asset('js/leaflet.js') }}"></script>
            
            <!-- Report Location Map JS -->
            <script src="{{ asset('js/report-location-map.js') }}"></script>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Prepare report data
                    const reportData = {
                        tracking_id: @json($violationReport->report_id),
                        violation_type: @json($violationReport->selected_violation_type),
                        status: @json($violationReport->status),
                        detected_barangay: @json($violationReport->effective_barangay),
                        assigned_barangay_office: @json($violationReport->assigned_barangay_office),
                        latitude: {{ $violationReport->latitude }},
                        longitude: {{ $violationReport->longitude }},
                        location_context: @json($violationReport->location_context ?? 'N/A'),
                        office_latitude: null,
                        office_longitude: null
                    };
                    
                    // Get barangay office coordinates from config if available
                    @php
                        $barangays = config('santa_cruz_barangays.barangays', []);
                        $officeCoords = null;
                        foreach ($barangays as $barangay) {
                            if (strcasecmp($barangay['name'], (string) $violationReport->effective_barangay) === 0) {
                                if (isset($barangay['center_lat']) && isset($barangay['center_lon'])) {
                                    $officeCoords = [
                                        'lat' => $barangay['center_lat'],
                                        'lng' => $barangay['center_lon']
                                    ];
                                }
                                break;
                            }
                        }
                    @endphp
                    
                    @if($officeCoords)
                        reportData.office_latitude = {{ $officeCoords['lat'] }};
                        reportData.office_longitude = {{ $officeCoords['lng'] }};
                    @endif
                    
                    const geojsonUrl = "{{ asset('gis/boundary.geojson') }}";
                    
                    // Initialize map
                    initializeReportLocationMap(reportData, geojsonUrl);
                });
            </script>
        @else
            <!-- No GPS Alert -->
            <div style="background: #fef3c7; padding: 2rem; text-align: center; border-radius: 0.5rem; border-left: 4px solid #f59e0b;">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">📍</div>
                <div style="font-size: 1.125rem; font-weight: 600; color: #92400e; margin-bottom: 0.5rem;">
                    GPS Location Not Available
                </div>
                <div style="color: #6b7280; font-size: 0.875rem; margin-bottom: 1rem;">
                    This report was submitted without GPS coordinates.
                </div>
                <div style="background: #ffffff; padding: 1rem; border-radius: 0.375rem; display: inline-block; text-align: left;">
                    <div style="margin-bottom: 0.5rem;">
                        <span style="color: #6b7280; font-size: 0.75rem;">TRACKING ID:</span>
                        <span style="color: #333333; font-weight: 600; margin-left: 0.5rem;">{{ $violationReport->report_id }}</span>
                    </div>
                    <div style="margin-bottom: 0.5rem;">
                        <span style="color: #6b7280; font-size: 0.75rem;">DETECTED BARANGAY:</span>
                        <span style="color: #333333; font-weight: 600; margin-left: 0.5rem;">{{ $violationReport->effective_barangay ?? 'Needs Barangay Review' }}</span>
                    </div>
                    <div>
                        <span style="color: #6b7280; font-size: 0.75rem;">STATUS:</span>
                        <span style="margin-left: 0.5rem;"><x-status-badge :status="$violationReport->status" size="sm" /></span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3 mb-6">
        @if($isBarangayView)
            <a href="{{ route('barangay.dashboard', ['barangay' => $barangayName]) }}" 
               class="btn btn-neutral btn-md text-white font-semibold">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <button type="button" onclick="toggleUpdateForm()" 
                    class="btn btn-warning btn-md text-gray-800 font-semibold" id="updateReportBtn">
                <i class="fas fa-edit"></i> Update Report
            </button>
        @else
            <a href="{{ route('violation-reports.index') }}" 
               class="btn btn-neutral btn-md text-white font-semibold">
                <i class="fas fa-arrow-left"></i> Back to Reports
            </a>
            {{-- DILG Admin: View Only (No Edit) - Barangay staff handles updates --}}
        @endif
    </div>

    <!-- Barangay Update Form (Hidden by default) -->
    @if($isBarangayView)
    <div id="updateForm" class="detail-card full-width-card" style="display: none; margin-top: 1.5rem;">
        <div class="detail-header">
            <h3 class="detail-title">✏️ Update Report Status</h3>
            <p style="color: #6b7280; font-size: 0.875rem; margin-top: 0.5rem;">
                You can update the status at any time, even after marking as Resolved or Rejected.
            </p>
        </div>

        <form action="{{ route('barangay.report.update', ['barangay' => $barangayName, 'report' => $violationReport]) }}" method="POST">
            @csrf
            @method('PUT')

            <div style="display: grid; gap: 1.5rem;">
                <!-- Status Update with Custom Dropdown -->
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: var(--dilg-dark-gray); font-size: 0.9375rem;">
                        Status <span style="color: red;">*</span>
                    </label>
                    
                    <input type="hidden" name="status" id="statusInput" value="{{ $violationReport->status }}" required>
                    
                    <!-- Custom Status Dropdown -->
                    <div class="status-dropdown-wrapper">
                        <button type="button" class="status-dropdown-trigger" id="statusDropdownTrigger">
                            <div class="status-dropdown-selected">
                                <i class="fas fa-circle" id="selectedIcon"></i>
                                <span id="selectedText">{{ $violationReport->status }}</span>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        
                        <div class="status-dropdown-menu" id="statusDropdownMenu">
                            <div class="status-dropdown-option" data-status="Submitted" data-color="#dbeafe" data-text-color="#1e40af" data-icon="fa-upload">
                                <div class="status-badge-inner">
                                    <i class="fas fa-upload"></i>
                                    <span>Submitted</span>
                                </div>
                            </div>
                            
                            <div class="status-dropdown-option" data-status="For Verification" data-color="#fef3c7" data-text-color="#92400e" data-icon="fa-search">
                                <div class="status-badge-inner">
                                    <i class="fas fa-search"></i>
                                    <span>For Verification</span>
                                </div>
                            </div>
                            
                            <div class="status-dropdown-option" data-status="Verified" data-color="#dbeafe" data-text-color="#1e40af" data-icon="fa-shield-alt">
                                <div class="status-badge-inner">
                                    <i class="fas fa-shield-alt"></i>
                                    <span>Verified</span>
                                </div>
                            </div>
                            
                            <div class="status-dropdown-option" data-status="Assigned" data-color="#e0e7ff" data-text-color="#3730a3" data-icon="fa-user-plus">
                                <div class="status-badge-inner">
                                    <i class="fas fa-user-plus"></i>
                                    <span>Assigned</span>
                                </div>
                            </div>
                            
                            <div class="status-dropdown-option" data-status="In Progress" data-color="#ddd6fe" data-text-color="#5b21b6" data-icon="fa-spinner">
                                <div class="status-badge-inner">
                                    <i class="fas fa-spinner"></i>
                                    <span>In Progress</span>
                                </div>
                            </div>
                            
                            <div class="status-dropdown-option" data-status="Action Taken" data-color="#ccfbf1" data-text-color="#115e59" data-icon="fa-check-square">
                                <div class="status-badge-inner">
                                    <i class="fas fa-check-square"></i>
                                    <span>Action Taken</span>
                                </div>
                            </div>
                            
                            <div class="status-dropdown-option" data-status="Resolved" data-color="#d1fae5" data-text-color="#065f46" data-icon="fa-check-circle">
                                <div class="status-badge-inner">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Resolved</span>
                                </div>
                            </div>
                            
                            <div class="status-dropdown-option" data-status="Rejected" data-color="#fee2e2" data-text-color="#991b1b" data-icon="fa-times-circle">
                                <div class="status-badge-inner">
                                    <i class="fas fa-times-circle"></i>
                                    <span>Rejected</span>
                                </div>
                            </div>
                            
                            <div class="status-dropdown-option" data-status="Closed" data-color="#fef9c3" data-text-color="#854d0e" data-icon="fa-archive">
                                <div class="status-badge-inner">
                                    <i class="fas fa-archive"></i>
                                    <span>Closed</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <small style="display: block; margin-top: 0.625rem; color: #6b7280; font-size: 0.75rem;">
                        💡 Click to select a status. You can change to any status if needed.
                    </small>
                    
                    <style>
                        .status-dropdown-wrapper {
                            position: relative;
                            width: 100%;
                        }
                        
                        .status-dropdown-trigger {
                            width: 100%;
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            padding: 0.75rem 1rem;
                            border: 2px solid #d1d5db;
                            border-radius: 0.5rem;
                            background: white;
                            cursor: pointer;
                            transition: all 0.2s;
                            font-size: 0.9375rem;
                        }
                        
                        .status-dropdown-trigger:hover {
                            border-color: var(--dilg-yellow);
                        }
                        
                        .status-dropdown-trigger.active {
                            border-color: var(--dilg-dark-gold);
                            box-shadow: 0 0 0 3px rgba(244, 197, 66, 0.1);
                        }
                        
                        .status-dropdown-selected {
                            display: flex;
                            align-items: center;
                            gap: 0.625rem;
                            font-weight: 500;
                        }
                        
                        .status-dropdown-trigger i.fa-chevron-down {
                            color: #9ca3af;
                            transition: transform 0.2s;
                        }
                        
                        .status-dropdown-trigger.active i.fa-chevron-down {
                            transform: rotate(180deg);
                        }
                        
                        .status-dropdown-menu {
                            position: absolute;
                            top: 100%;
                            left: 0;
                            right: 0;
                            margin-top: 0.5rem;
                            background: white;
                            border: 2px solid #e5e7eb;
                            border-radius: 0.5rem;
                            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
                            max-height: 320px;
                            overflow-y: auto;
                            z-index: 1000;
                            display: none;
                        }
                        
                        .status-dropdown-menu.show {
                            display: block;
                            animation: slideDown 0.2s ease-out;
                        }
                        
                        .status-dropdown-option {
                            display: flex;
                            align-items: center;
                            gap: 0.75rem;
                            padding: 0.5rem 1rem;
                            cursor: pointer;
                            transition: all 0.15s;
                            font-size: 0.9375rem;
                            font-weight: 500;
                        }
                        
                        .status-dropdown-option:last-child {
                            border-bottom: none;
                        }
                        
                        .status-dropdown-option .status-badge-inner {
                            display: flex;
                            align-items: center;
                            gap: 0.5rem;
                            padding: 0.5rem 0.875rem;
                            border-radius: 0.375rem;
                            transition: all 0.15s;
                        }
                        
                        .status-dropdown-option:hover {
                            background: #f9fafb;
                        }
                        
                        .status-dropdown-option.selected .status-badge-inner {
                            font-weight: 600;
                            box-shadow: 0 0 0 2px var(--dilg-dark-gold);
                        }
                        
                        .status-dropdown-option i {
                            font-size: 1rem;
                        }
                        
                        /* Scrollbar styling */
                        .status-dropdown-menu::-webkit-scrollbar {
                            width: 6px;
                        }
                        
                        .status-dropdown-menu::-webkit-scrollbar-track {
                            background: #f3f4f6;
                            border-radius: 0 0.5rem 0.5rem 0;
                        }
                        
                        .status-dropdown-menu::-webkit-scrollbar-thumb {
                            background: #d1d5db;
                            border-radius: 3px;
                        }
                        
                        .status-dropdown-menu::-webkit-scrollbar-thumb:hover {
                            background: #9ca3af;
                        }
                        
                        @keyframes slideDown {
                            from {
                                opacity: 0;
                                transform: translateY(-10px);
                            }
                            to {
                                opacity: 1;
                                transform: translateY(0);
                            }
                        }
                    </style>
                    
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const trigger = document.getElementById('statusDropdownTrigger');
                            const menu = document.getElementById('statusDropdownMenu');
                            const statusInput = document.getElementById('statusInput');
                            const selectedText = document.getElementById('selectedText');
                            const selectedIcon = document.getElementById('selectedIcon');
                            const options = document.querySelectorAll('.status-dropdown-option');
                            const currentStatus = '{{ $violationReport->status }}';
                            
                            // Set initial colors for all options
                            options.forEach(option => {
                                const bgColor = option.getAttribute('data-color');
                                const textColor = option.getAttribute('data-text-color');
                                const innerBadge = option.querySelector('.status-badge-inner');
                                
                                innerBadge.style.backgroundColor = bgColor;
                                innerBadge.style.color = textColor;
                                
                                // Mark current status as selected
                                if (option.getAttribute('data-status') === currentStatus) {
                                    option.classList.add('selected');
                                    // Update trigger display
                                    const icon = option.getAttribute('data-icon');
                                    selectedIcon.className = 'fas ' + icon;
                                    selectedIcon.style.color = textColor;
                                    selectedText.style.color = textColor;
                                }
                            });
                            
                            // Toggle dropdown
                            trigger.addEventListener('click', function(e) {
                                e.stopPropagation();
                                menu.classList.toggle('show');
                                trigger.classList.toggle('active');
                            });
                            
                            // Select option
                            options.forEach(option => {
                                option.addEventListener('click', function() {
                                    const status = this.getAttribute('data-status');
                                    const textColor = this.getAttribute('data-text-color');
                                    const icon = this.getAttribute('data-icon');
                                    
                                    // Update hidden input
                                    statusInput.value = status;
                                    
                                    // Update trigger display
                                    selectedText.textContent = status;
                                    selectedText.style.color = textColor;
                                    selectedIcon.className = 'fas ' + icon;
                                    selectedIcon.style.color = textColor;
                                    
                                    // Update selected state
                                    options.forEach(opt => opt.classList.remove('selected'));
                                    this.classList.add('selected');
                                    
                                    // Close dropdown
                                    menu.classList.remove('show');
                                    trigger.classList.remove('active');
                                });
                            });
                            
                            // Close dropdown when clicking outside
                            document.addEventListener('click', function(e) {
                                if (!trigger.contains(e.target) && !menu.contains(e.target)) {
                                    menu.classList.remove('show');
                                    trigger.classList.remove('active');
                                }
                            });
                        });
                    </script>
                </div>

                <!-- Assigned Personnel -->
                <div>
                    <label for="assigned_personnel" style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--dilg-dark-gray);">
                        Assigned Personnel
                    </label>
                    <input type="text" name="assigned_personnel" id="assigned_personnel" 
                           value="{{ $violationReport->assigned_personnel }}"
                           placeholder="Enter personnel name (e.g., Brgy. Tanod Juan Dela Cruz)"
                           style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem;">
                </div>

                <!-- Action Taken -->
                <div>
                    <label for="action_taken" style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--dilg-dark-gray);">
                        Action Taken
                    </label>
                    <textarea name="action_taken" id="action_taken" rows="3"
                              placeholder="Describe the action taken to resolve this violation..."
                              style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem;">{{ $violationReport->action_taken }}</textarea>
                </div>

                <!-- Remarks -->
                <div>
                    <label for="remarks" style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--dilg-dark-gray);">
                        Remarks
                    </label>
                    <textarea name="remarks" id="remarks" rows="2"
                              placeholder="Add any additional remarks or notes..."
                              style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem;">{{ $violationReport->remarks }}</textarea>
                </div>

                <!-- Submit Buttons -->
                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="btn btn-success btn-md text-white font-semibold" id="saveUpdatesBtn">
                        <i class="fas fa-save"></i> Save Updates
                    </button>
                    <button type="button" onclick="toggleUpdateForm()" class="btn btn-error btn-md text-white font-semibold">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function toggleUpdateForm() {
            const form = document.getElementById('updateForm');
            if (form.style.display === 'none') {
                form.style.display = 'block';
                form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                form.style.display = 'none';
            }
        }

        // AJAX Status Update - No Page Refresh
        document.addEventListener('DOMContentLoaded', function() {
            const updateForm = document.querySelector('#updateForm form');
            if (!updateForm) return;

            updateForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const saveBtn = document.getElementById('saveUpdatesBtn');
                const originalBtnHTML = saveBtn.innerHTML;
                
                // Show loading state
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="loading loading-spinner loading-xs"></span> Updating...';

                const formData = new FormData(this);
                const url = this.action;

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Show success message
                        showAlert('success', data.message || 'Status updated successfully!');
                        
                        // Update status badge on page (no full reload)
                        const statusBadge = document.querySelector('.detail-header .badge');
                        if (statusBadge && data.data && data.data.status) {
                            const newStatus = data.data.status;
                            statusBadge.textContent = newStatus;
                            // Update badge class
                            statusBadge.className = 'badge badge-' + newStatus.toLowerCase().replace(/ /g, '');
                        }

                        // Hide form
                        toggleUpdateForm();
                        
                        // Reload page to refresh timeline (simple solution)
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showAlert('error', data.message || 'Failed to update status');
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = originalBtnHTML;
                    }
                } catch (error) {
                    console.error('Update error:', error);
                    showAlert('error', 'Network error. Please try again.');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalBtnHTML;
                }
            });
        });

        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'error'} fixed top-4 right-4 w-auto max-w-md shadow-lg z-50`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }
    </script>
    @endif
</div>
@endsection
