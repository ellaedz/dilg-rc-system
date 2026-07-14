# DILG-RC - Road Clearing Violation Reporting System

> **Phase 4D correction status (July 2026):** The available `boundary.geojson` is a
> Santa Cruz **municipal** boundary, not a barangay boundary dataset. The system
> automatically validates municipal coverage, but it does not invent a barangay.
> When `public/gis/santa_cruz_barangays.geojson` is unavailable, reports enter the
> DILG **Needs Barangay Review** queue for confirmed temporary routing.

## Current GIS behavior

- Municipality coverage validation: automatic using `public/gis/boundary.geojson`.
- Barangay polygon detection: ready for `public/gis/santa_cruz_barangays.geojson`, but the dataset is currently unavailable.
- Safe fallback: DILG Admin manually routes an unassigned report with a required reason and confirmation.
- Barangay visibility: based on `effective_barangay` (`detected_barangay` first, otherwise `manually_assigned_barangay`).
- Nearest barangay office: Haversine-based follow-up recommendation only; it never decides jurisdiction.
- Office coordinates: 20 researcher-verified Google Maps entries are active; 6 config-centroid fallbacks remain provisional.

### Final GIS data required for production

1. An official or independently validated barangay polygon FeatureCollection with one feature per barangay and a `barangay`, `name`, or `ADM4_EN` property.
2. Validated barangay hall coordinates with their source and validation status recorded.

The expected barangay polygon path is `public/gis/santa_cruz_barangays.geojson`.

## 🏛️ Department of the Interior and Local Government
### Santa Cruz, Laguna - Road Clearing Operations

<p align="center">
<img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
<img src="https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
<img src="https://img.shields.io/badge/Tailwind_CSS-4.0-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind">
<img src="https://img.shields.io/badge/DaisyUI-Latest-5A0EF8?style=for-the-badge" alt="DaisyUI">
<img src="https://img.shields.io/badge/Phase-4D_COMPLETED-10b981?style=for-the-badge" alt="Phase 4D">
</p>

---

## 📖 About DILG-RC System

DILG-RC is a comprehensive road clearing violation reporting and monitoring system designed for the Department of the Interior and Local Government (DILG) in Santa Cruz, Laguna. The system enables efficient reporting, verification, tracking, and resolution of road clearing violations across 26 barangays.

### ✅ Key Features

- **🏛️ Role-Based Authentication** - DILG Admin and Barangay Staff with distinct access levels
- **📊 Real-Time Analytics** - Comprehensive violation statistics and performance metrics
- **🏘️ Barangay Performance Ranking** - Transparent ranking system with resolution rates
- **📈 Status Transparency Timeline** - Complete audit trail of report status changes
- **📄 Printable Reports** - Professional auto-generated reports for compliance
- **🎯 Smart Filtering** - Role-based data filtering and barangay assignment
- **📱 Responsive Design** - Modern Tailwind CSS + DaisyUI interface with DILG yellow/gold theme
- **🗺️ GIS Boundary Map** - Interactive Leaflet.js map with barangay boundaries
- **🔒 Anonymous Citizen Reporting** - Privacy-focused reporting with tracking ID system

---

## 🔒 ANONYMOUS CITIZEN REPORTING (Phase 4A.1)

**Citizens may submit reports anonymously.** The system only requires:
- ✅ Report evidence (photo)
- ✅ GPS location (latitude, longitude)
- ✅ Timestamp
- ✅ Selected violation type

**No personal information required:**
- ❌ No name
- ❌ No email
- ❌ No address
- ❌ No account registration

**Optional:** Contact number (for barangay follow-up only)

**How it works:**
1. Citizen submits report WITHOUT providing identity
2. System generates unique **Tracking ID** (e.g., RCV-2026-0001)
3. Citizen saves Tracking ID
4. Citizen uses ONLY Tracking ID to check report status
5. Barangay handles real-world identity verification separately (outside system)

**API Endpoints:**
- `POST /api/mobile/reports` - Submit anonymous report
- `GET /api/mobile/reports/status/{tracking_id}` - Check report status

**Privacy Protection:**
- Report submission does NOT require citizen name or contact details
- API responses do NOT expose reporter identity
- Only barangay staff can view optional contact number (if provided)
- Tracking ID is the ONLY identifier citizens need

---

## ✨ PHASE 3 COMPLETED FEATURES

### 🔐 Phase 3A - Authentication & Role-Based Access
- ✅ Secure login system with role validation
- ✅ DILG Admin role: Full monitoring access across all barangays
- ✅ Barangay Staff role: Restricted to assigned barangay only
- ✅ Middleware protection for routes and data
- ✅ 27 test accounts (1 DILG Admin + 26 Barangay Staff)

### 📊 Phase 3B - DILG Admin Analytics Dashboard
- ✅ DILG-wide violation statistics overview
- ✅ Total reports across all 26 barangays
- ✅ Violation type distribution
- ✅ Status summary with percentages
- ✅ Top performing barangay highlight
- ✅ Most recurring violation identification
- ✅ Monthly trend analysis (last 6 months)
- ✅ Resolved vs Pending comparison

### 🏘️ Phase 3C - Barangay Transparency Analytics
- ✅ Barangay-specific violation statistics
- ✅ Resolution rate and performance metrics
- ✅ Status distribution charts
- ✅ Violation type breakdown by barangay
- ✅ Recent actions taken table
- ✅ Response transparency summary
- ✅ Average response time tracking
- ✅ Only shows data for assigned barangay (Barangay Staff)

### 🏆 Phase 3D - Barangay Performance Ranking
- ✅ Comprehensive performance ranking table
- ✅ All 26 Santa Cruz barangays listed
- ✅ Performance score calculation (0-100)
- ✅ Resolution rate percentage
- ✅ Average response time (hours)
- ✅ Total/Verified/Resolved/Pending counts
- ✅ Rating system: Excellent, Good, Needs Improvement, Critical, No Data
- ✅ DILG Admin only access

### 📅 Phase 3E - Status Transparency Timeline
- ✅ Complete status change history for each report
- ✅ Timeline visualization with colored status badges
- ✅ Action taken documentation
- ✅ Personnel assignment tracking
- ✅ Timestamp for each status update
- ✅ Remarks and notes system
- ✅ Visual status indicators
- ✅ Supports all statuses: Submitted → For Verification → Verified → Assigned → In Progress → Action Taken → Resolved/Closed
- ✅ Rejection flow: Submitted → For Verification → Rejected

### 📄 Phase 3F - Printable Auto-Generated Reports
- ✅ Official government document format
- ✅ DILG-wide summary report (DILG Admin only)
- ✅ Barangay-specific summary report
- ✅ Professional print CSS (hides sidebar/buttons)
- ✅ Official DILG header with logo
- ✅ Summary statistics cards
- ✅ Violation type summary tables
- ✅ Status summary with percentages
- ✅ Top performing barangay highlight
- ✅ Barangay needing attention callout
- ✅ Recent actions taken table
- ✅ Signature section (Prepared by / Noted by)
- ✅ System-generated disclaimer note
- ✅ Export placeholders (CSV/PDF) for Phase 17
- ✅ Print button with browser print dialog

### 🧹 Phase 3G - Final Phase 3 Verification
- ✅ All routes verified and accessible
- ✅ Role-based access properly enforced
- ✅ Middleware protection confirmed
- ✅ Layouts consistent (DILG yellow/gold theme)
- ✅ Sidebar menus correct for each role
- ✅ Dashboard cards functioning
- ✅ Analytics pages working
- ✅ Performance ranking operational
- ✅ Status timelines displaying correctly
- ✅ Printable reports generating properly
- ✅ Export placeholders showing correct messages
- ✅ UI consistency maintained throughout
- ✅ No broken links or routes
- ✅ Dummy data cleaned (road clearing violations only)
- ✅ README.md updated with Phase 3 completion

### 🗺️ Phase 4B - GIS Boundary Integration
- ✅ Leaflet.js interactive map integration (local installation via npm)
- ✅ OpenStreetMap tile layer
- ✅ GeoJSON boundary file support (public/gis/boundary.geojson)
- ✅ Santa Cruz, Laguna barangay boundaries display
- ✅ Clickable barangay popups with names
- ✅ Auto-fit map to boundary layer
- ✅ Intelligent barangay name detection from GeoJSON properties
- ✅ Gold boundary styling with hover effects
- ✅ Map legend with future features preview
- ✅ Boundary information sidebar
- ✅ Graceful handling if GeoJSON file is missing
- ✅ Role-aware layout (DILG Admin and Barangay Staff access)
- ✅ Professional DILG yellow/gold theme
- ✅ Production ready: All Leaflet assets stored locally (no CDN dependency)
- ✅ Foundation for Phase 4C (GPS detection) and Phase 4D (markers & clustering)

### 📍 Phase 4C - GPS Barangay Auto-Assignment API
- ✅ Point-in-polygon algorithm for accurate GPS detection
- ✅ GeoJSON boundary-based barangay assignment
- ✅ Ray casting algorithm implementation
- ✅ Support for Polygon and MultiPolygon geometries
- ✅ Automatic barangay assignment from mobile GPS coordinates
- ✅ API endpoint: `POST /api/gis/detect-barangay`
- ✅ Mobile report API integration with GPS auto-assignment
- ✅ Coordinate order handling (mobile: lat/lon, GeoJSON: lon/lat)
- ✅ Anonymous citizen reporting with GPS-based assignment
- ✅ Tracking ID system for status monitoring
- ✅ Fallback to config-based bounding boxes if GeoJSON fails
- ✅ Comprehensive error handling:
  - Missing GPS coordinates
  - Outside Santa Cruz coverage
  - GeoJSON file missing or invalid
- ✅ Multiple barangay name property support in GeoJSON
- ✅ Location context reporting (Inside/Outside Boundary, GPS Missing, etc.)
- ✅ Report visibility rules: DILG sees all, Barangay sees only assigned
- ✅ Status tracking API without exposing citizen identity

### 🗺️ Phase 4D - GIS Report Markers, Clustering, and Hotspots
- ✅ API endpoints: `GET /api/gis/reports`, `/barangay-offices`, `/hotspots-summary`
- ✅ Interactive report markers with GPS coordinates on GIS map
- ✅ Marker clustering using Leaflet.markercluster plugin
- ✅ Status-based marker colors (matching DaisyUI badges)
- ✅ Hotspot summary cards (Total Reports, Top Barangay, Most Common Violation/Status)
- ✅ Filter panel (Barangay, Violation Type, Status with Apply/Reset buttons)
- ✅ Barangay office location markers (gold/DILG theme)
- ✅ Report marker popups with full details and recommended office
- ✅ Office marker popups with address and follow-up note
- ✅ Barangay office recommendation panel (shows on marker click)
- ✅ Role-based filtering: DILG Admin sees all, Barangay Staff sees only assigned
- ✅ Visible marker count display
- ✅ Map legend with all marker types
- ✅ Demo barangay office coordinates (to be validated by LGU)
- ✅ Filter logic for barangay, violation type, and status
- ✅ Graceful fallback if clustering plugin unavailable
- ✅ Phase 4B boundaries and Phase 4C GPS detection preserved
- ✅ JavaScript file: `public/js/gis-markers.js`

---

## 🎯 CURRENT SYSTEM STATUS

**✅ Phase 3 COMPLETE** - All transparency, analytics, authentication, ranking, timeline, and printable report features are fully functional and verified.

**✅ Phase 4B COMPLETE** - GIS Boundary Integration with Leaflet.js interactive map displaying Santa Cruz, Laguna barangay boundaries from boundary.geojson.

**✅ Phase 4C COMPLETE** - GPS Barangay Auto-Assignment API with GeoJSON point-in-polygon detection for accurate barangay assignment from mobile GPS coordinates.

**✅ Phase 4D COMPLETE** - GIS Report Markers, Clustering, and Hotspots with interactive map markers, marker clustering, hotspot analytics, barangay office recommendations, and filter functionality.

### System Architecture

**Roles:**
- **DILG Admin** - Read-only monitoring access across all 26 barangays
- **Barangay Staff** - Full update permissions for assigned barangay only

**26 Santa Cruz, Laguna Barangays:**
Alipit, Bagumbayan, Bubukal, Calios, Duhat, Gatid, Jasaan, Labuin, Malinao, Oogong, Pagsawitan, Palasan, Patimbao, Poblacion I, Poblacion II, Poblacion III, Poblacion IV, Poblacion V, San Jose, San Juan, San Pablo Norte, San Pablo Sur, Santisima Cruz, Santo Angel Central, Santo Angel Norte, Santo Angel Sur

**Authentication:**
- Login Route: `/login`
- 27 Test Accounts (see `ACCOUNTS_CREDENTIALS.md`)
- All passwords: `password` (for proposal/testing)

**DILG Admin Routes:**
- `/dilg-dashboard` - DILG-wide analytics dashboard
- `/violation-reports` - All violation reports (read-only)
- `/violation-reports/{id}` - Individual report details
- `/barangay-performance` - Performance ranking table
- `/analytics-reports` - DILG-wide analytics & reports
- `/analytics-reports/print` - Printable DILG-wide report
- `/ai-analytics` - Placeholder for Phase 4A
- `/gis-map` - **GIS Boundary Map (Phase 4B ✅)**
- `/profile` - Profile page placeholder

**Barangay Staff Routes:**
- `/barangay/{barangay}/dashboard` - Barangay dashboard
- `/barangay/{barangay}/incoming-reports` - New reports for verification
- `/barangay/{barangay}/verified-reports` - Verified reports for assignment
- `/barangay/{barangay}/response-tracking` - Track ongoing reports
- `/barangay/{barangay}/analytics-reports` - Barangay analytics
- `/barangay/{barangay}/analytics-reports/print` - Printable barangay report
- `/violation-reports/{id}` - Individual report details (assigned barangay only)
- `/barangay/{barangay}/profile` - Profile page placeholder

**Violation Types (Road Clearing Only):**
- Illegal Parking
- Road Obstruction
- Sidewalk Obstruction
- Vending Obstruction
- Construction Materials Obstruction
- Encroachment
- Abandoned Vehicle
- Illegal Structure
- Waste/Garbage Obstruction
- Other Road Clearing Violation

**Status Flow:**
1. Submitted (citizen report)
2. For Verification (barangay review)
3. Verified (valid violation confirmed) OR Rejected (invalid/duplicate)
4. Assigned (personnel assigned)
5. In Progress (action being taken)
6. Action Taken (enforcement completed)
7. Resolved (violation cleared)
8. Closed (case closed)

---

## 📍 PHASE 4C - GPS BARANGAY AUTO-ASSIGNMENT

### Purpose

Automatically detect and assign the correct barangay to violation reports based on GPS coordinates using accurate GeoJSON boundary polygons. This enables true anonymous citizen reporting where the system automatically determines jurisdiction without requiring citizens to specify their location.

### How It Works

1. **Citizen submits report** with GPS coordinates (latitude, longitude) from mobile app
2. **System loads** `public/gis/boundary.geojson`
3. **Point-in-polygon algorithm** checks if GPS point is inside any barangay boundary
4. **Barangay detected** - Report automatically assigned to correct barangay
5. **Citizen receives** Tracking ID for status monitoring
6. **Report appears** in assigned barangay's dashboard

### Coordinate Order (IMPORTANT!)

**Mobile GPS sends:**
```json
{
  "latitude": 14.2819,
  "longitude": 121.4166
}
```

**GeoJSON stores coordinates as:**
```json
[longitude, latitude]  // [121.4166, 14.2819]
```

⚠️ **The system handles this conversion automatically** - Do NOT reverse coordinates manually!

### API Endpoints

#### 1. Detect Barangay from GPS

**Endpoint:** `POST /api/gis/detect-barangay`

**Purpose:** Test GPS detection before submitting actual report

**Request:**
```json
{
  "latitude": 14.2819,
  "longitude": 121.4166
}
```

**Response (Success - Inside Coverage):**
```json
{
  "success": true,
  "message": "Barangay detected successfully",
  "data": {
    "detected_barangay": "Bagumbayan",
    "assigned_barangay_office": "Barangay Hall - Bagumbayan",
    "location_context": "Inside Barangay Boundary",
    "is_inside_coverage": true
  }
}
```

**Response (Outside Coverage):**
```json
{
  "success": true,
  "message": "Barangay detected successfully",
  "data": {
    "detected_barangay": "Outside Santa Cruz Coverage",
    "assigned_barangay_office": "Needs DILG Review",
    "location_context": "Outside Boundary",
    "is_inside_coverage": false
  }
}
```

**Response (GPS Missing):**
```json
{
  "success": true,
  "message": "Barangay detected successfully",
  "data": {
    "detected_barangay": "Location Not Available",
    "assigned_barangay_office": "Unassigned",
    "location_context": "GPS Missing",
    "is_inside_coverage": false
  }
}
```

**Response (Validation Error):**
```json
{
  "success": false,
  "message": "Invalid GPS coordinates",
  "errors": {
    "latitude": ["The latitude field is required."],
    "longitude": ["The longitude must be between -180 and 180."]
  }
}
```

#### 2. Submit Anonymous Report with GPS Auto-Assignment

**Endpoint:** `POST /api/mobile/reports`

**Request:**
```json
{
  "description": "Large delivery truck illegally parked blocking the entire lane on Main Street. Has been there for 3 days causing heavy traffic congestion.",
  "selected_violation_type": "Illegal Parking",
  "latitude": 14.2819,
  "longitude": 121.4166,
  "timestamp": "2026-07-06T10:30:00Z",
  "image": "<file upload>",
  "contact_number": "09171234567",
  "gps_accuracy": 5.2
}
```

**Required Fields:**
- `description` - Report description
- `selected_violation_type` - Type of violation
- `latitude` - GPS latitude (-90 to 90)
- `longitude` - GPS longitude (-180 to 180)
- `timestamp` - Report submission time
- `image` - Photo evidence (optional but recommended)

**Optional Fields:**
- `contact_number` - For barangay follow-up only
- `gps_accuracy` - GPS accuracy in meters

**NOT Required:**
- ❌ No name
- ❌ No email
- ❌ No address
- ❌ No account

**Response:**
```json
{
  "success": true,
  "message": "Report submitted successfully",
  "data": {
    "report_id": "RCV-2026-0001",
    "tracking_id": "RCV-2026-0001",
    "selected_violation_type": "Illegal Parking",
    "status": "Submitted",
    "verification_status": "Unverified",
    "detected_barangay": "Bagumbayan",
    "assigned_barangay_office": "Barangay Hall - Bagumbayan",
    "location_context": "Inside Barangay Boundary",
    "note": "Please save your Tracking ID to check the status of your report.",
    "description": "Large delivery truck illegally parked...",
    "latitude": 14.2819,
    "longitude": 121.4166,
    "gps_accuracy": 5.2,
    "image_url": "http://127.0.0.1:8000/storage/reports/RCV-2026-0001_1720259400.jpg",
    "timestamp": "2026-07-06T10:30:00.000000Z",
    "date_submitted": "2026-07-06",
    "created_at": "2026-07-06T10:30:45.000000Z"
  }
}
```

#### 3. Check Report Status (Anonymous)

**Endpoint:** `GET /api/mobile/reports/status/{tracking_id}`

**Example:** `GET /api/mobile/reports/status/RCV-2026-0001`

**Response:**
```json
{
  "success": true,
  "message": "Report status retrieved successfully",
  "data": {
    "tracking_id": "RCV-2026-0001",
    "current_status": "Verified",
    "verification_status": "Valid Violation",
    "detected_barangay": "Bagumbayan",
    "assigned_barangay_office": "Barangay Hall - Bagumbayan",
    "latest_action": "Report verified by barangay staff. Personnel assigned for action.",
    "last_updated": "2026-07-06T14:25:30.000000Z",
    "date_submitted": "2026-07-06"
  }
}
```

**Note:** This endpoint does NOT expose citizen identity (no name, email, or contact shown)

### Testing with Postman

#### Test 1: Detect Barangay
```
POST http://127.0.0.1:8000/api/gis/detect-barangay
Content-Type: application/json

{
  "latitude": 14.2819,
  "longitude": 121.4166
}
```

#### Test 2: Submit Report
```
POST http://127.0.0.1:8000/api/mobile/reports
Content-Type: multipart/form-data

description: Large delivery truck illegally parked
selected_violation_type: Illegal Parking
latitude: 14.2819
longitude: 121.4166
timestamp: 2026-07-06T10:30:00Z
image: [select file]
contact_number: 09171234567
gps_accuracy: 5.2
```

#### Test 3: Check Status
```
GET http://127.0.0.1:8000/api/mobile/reports/status/RCV-2026-0001
```

### Point-in-Polygon Algorithm

The system uses the **Ray Casting Algorithm** to determine if a GPS point is inside a polygon:

1. Cast a ray from the point to infinity
2. Count how many times the ray crosses polygon edges
3. If odd number of crossings = inside polygon
4. If even number of crossings = outside polygon

**Supported GeoJSON Types:**
- `Polygon` - Single polygon
- `MultiPolygon` - Multiple polygons (e.g., barangays with islands)
- `FeatureCollection` - Collection of features

**Supported Barangay Name Properties:**
- `name`, `Name`, `NAME`
- `barangay`, `Barangay`, `BARANGAY`
- `brgy`, `Brgy`, `BRGY`
- `BGY_NAME`, `BRGY_NAME`
- `ADM4_EN`, `NAME_4`

### Error Handling

**Case 1: GPS Missing**
```json
{
  "detected_barangay": "Location Not Available",
  "assigned_barangay_office": "Unassigned",
  "location_context": "GPS Missing",
  "is_inside_coverage": false
}
```

**Case 2: Outside Santa Cruz**
```json
{
  "detected_barangay": "Outside Santa Cruz Coverage",
  "assigned_barangay_office": "Needs DILG Review",
  "location_context": "Outside Boundary",
  "is_inside_coverage": false
}
```

**Case 3: GeoJSON File Missing**
- Falls back to config-based bounding box detection
- If fallback also fails:
```json
{
  "detected_barangay": "Barangay Detection Unavailable",
  "assigned_barangay_office": "Needs Review",
  "location_context": "GeoJSON Missing or Invalid",
  "is_inside_coverage": false
}
```

### Report Visibility Rules

**DILG Admin:**
- ✅ Can view ALL reports from ALL barangays
- ✅ Read-only monitoring access

**Barangay Staff:**
- ✅ Can view ONLY reports where `detected_barangay` matches `assigned_barangay`
- ✅ Full update permissions for assigned reports

### Files Created/Modified

**Created:**
- `app/Http/Controllers/Api/GISApiController.php` - GPS detection API
- Enhanced `app/Services/BarangayAssignmentService.php` - Point-in-polygon logic

**Modified:**
- `app/Http/Controllers/Api/MobileReportApiController.php` - Integrated GPS auto-assignment
- `routes/api.php` - Added `/api/gis/detect-barangay` endpoint
- `README.md` - Phase 4C documentation

**Using:**
- `public/gis/boundary.geojson` - Barangay boundaries (from Phase 4B)
- `config/santa_cruz_barangays.php` - Fallback bounding boxes

---

## 📍 PHASE 4D - GIS REPORT MARKERS, CLUSTERING, AND HOTSPOTS

### ✅ PHASE 4D COMPLETE!

Phase 4D enhances the GIS map with interactive violation report markers, clustering, hotspot analysis, and barangay office recommendations.

### Purpose

Transform the GIS map into a real-time monitoring dashboard that shows:
1. **Violation report markers** with GPS coordinates
2. **Marker clustering** for better readability when zoomed out
3. **Hotspot summary statistics** (total reports, top barangay, most common violations)
4. **Barangay office locations** for follow-up recommendations
5. **Filter functionality** (by barangay, violation type, status)
6. **Barangay office recommendations** when clicking report markers

### Features Implemented

#### 1. Hotspot Summary Cards

Four pastel-gradient metric cards display real-time statistics:
- **Total Mapped Reports** - Count of all reports with GPS coordinates
- **Top Hotspot Barangay** - Barangay with highest report count
- **Most Common Violation** - Most frequently reported violation type
- **Most Common Status** - Most frequent report status

#### 2. Filter Panel

Horizontal filter bar with three dropdowns:
- **Barangay** - Filter by detected_barangay
- **Violation Type** - Filter by selected_violation_type
- **Status** - Filter by report status

Buttons:
- **Apply Filters** - Show only matching markers
- **Reset** - Clear all filters and show all markers

#### 3. Report Markers with Clustering

**Marker Features:**
- Color-coded by status (matching DaisyUI badge colors)
- Automatic clustering when zoomed out (via Leaflet.markercluster plugin)
- Click to zoom into cluster
- Individual marker popups with full report details

**Status Colors:**
- Blue (#3b82f6) - Submitted
- Orange (#f59e0b) - For Verification, In Progress
- Green (#10b981) - Verified, Resolved
- Indigo (#6366f1) - Assigned
- Purple (#a855f7) - In Progress
- Pink (#ec4899) - Action Taken
- Red (#ef4444) - Rejected
- Gray (#6b7280) - Closed

**Report Popup Contents:**
- Tracking ID
- Violation Type
- Status badge (colored)
- Verification status badge
- Detected Barangay
- Recommended Barangay Office for Follow-up
- "View Report Details" button

#### 4. Barangay Office Markers

**Office Marker Features:**
- Gold/yellow color (DILG theme)
- Shows barangay office locations
- Click for popup with office details

**Office Popup Contents:**
- Office Name
- Barangay
- Address
- Note: "Recommended follow-up office for reports assigned to this barangay"

**Demo Coordinates:**
```php
// NOTE: These are demo coordinates near Santa Cruz, Laguna
// Should be validated and updated by the LGU
[
    'Bagumbayan' => [14.2815, 121.4162],
    'Poblacion I' => [14.2791, 121.4157],
    'Poblacion II' => [14.2783, 121.4171],
    'Poblacion III' => [14.2797, 121.4181],
    'Alipit' => [14.2651, 121.4092],
    // Add more as validated by LGU
]
```

#### 5. Barangay Office Recommendation Panel

**When user clicks a report marker:**
- Recommendation panel appears in sidebar
- Shows:
  - Tracking ID
  - Detected Barangay
  - Report Status
  - **Recommended Barangay Office for Follow-up** (highlighted)
  - Office Address

**Wording:**
- ✅ "Recommended Barangay Office for Follow-up"
- ❌ NOT "Emergency Dispatch" or "Emergency Response"
- This is a road-clearing report system, not emergency response

#### 6. Map Legend

Updated legend shows:
- Barangay Boundary (gold outline)
- Pending Report (red marker)
- In Progress Report (orange marker)
- Resolved Report (green marker)
- Barangay Office (gold marker)

### API Endpoints

#### 1. Get GIS Reports

**Endpoint:** `GET /api/gis/reports`

**Query Parameters (optional):**
- `barangay` - Filter by detected_barangay
- `violation_type` - Filter by selected_violation_type
- `status` - Filter by status

**Response:**
```json
{
  "success": true,
  "message": "GIS reports retrieved successfully",
  "data": [
    {
      "report_id": "RCV-2026-0001",
      "tracking_id": "RCV-2026-0001",
      "selected_violation_type": "Illegal Parking",
      "status": "Submitted",
      "verification_status": "Unverified",
      "detected_barangay": "Bagumbayan",
      "assigned_barangay_office": "Barangay Hall - Bagumbayan",
      "latitude": 14.2819,
      "longitude": 121.4166,
      "timestamp": "2026-07-07 10:30:00",
      "location_context": "Inside Barangay Boundary"
    }
  ]
}
```

**Role-Based Filtering:**
- **DILG Admin** - Sees all reports
- **Barangay Staff** - Sees only reports where detected_barangay matches assigned_barangay

#### 2. Get Barangay Offices

**Endpoint:** `GET /api/gis/barangay-offices`

**Response:**
```json
{
  "success": true,
  "message": "Barangay offices retrieved successfully",
  "data": [
    {
      "barangay": "Bagumbayan",
      "office_name": "Barangay Hall - Bagumbayan",
      "latitude": 14.2815,
      "longitude": 121.4162,
      "address": "Bagumbayan, Santa Cruz, Laguna"
    }
  ],
  "note": "Demo coordinates - should be validated by LGU"
}
```

#### 3. Get Hotspot Summary

**Endpoint:** `GET /api/gis/hotspots-summary`

**Response:**
```json
{
  "success": true,
  "message": "Hotspot summary retrieved successfully",
  "data": {
    "total_mapped_reports": 20,
    "top_hotspot_barangay": "Bagumbayan",
    "most_common_violation_type": "Illegal Parking",
    "most_common_status": "Submitted",
    "barangay_report_counts": {
      "Bagumbayan": 5,
      "Poblacion I": 3
    },
    "violation_type_counts": {
      "Illegal Parking": 8,
      "Road Obstruction": 4
    },
    "status_counts": {
      "Submitted": 10,
      "Resolved": 5
    }
  }
}
```

### Marker Clustering

**Using Leaflet.markercluster plugin:**
- Currently loaded via CDN (temporary for Phase 4D)
- CDN: `https://unpkg.com/leaflet.markercluster@1.5.3/`
- TODO: Move to local assets in production

**Clustering Behavior:**
- Nearby reports group together when zoomed out
- Clicking cluster zooms in to show individual markers
- Individual markers show full popup on click
- Cluster radius: 50px

**Fallback:**
- If MarkerCluster plugin fails to load
- Falls back to normal markers (no clustering)
- Console warning displayed

### Filter Logic

**How Filters Work:**
1. User selects filters (barangay, violation type, status)
2. Clicks "Apply Filters"
3. JavaScript removes existing report markers
4. Adds only markers matching all selected filters
5. Updates "Visible Markers" count
6. Barangay boundaries and office markers remain visible

**Reset:**
- Clears all filter dropdowns
- Redisplays all report markers
- Updates count

### Role-Based Behavior

**DILG Admin:**
- ✅ Sees all report markers from all barangays
- ✅ Sees all barangay offices
- ✅ Sees all hotspot statistics
- ✅ Can filter by any barangay

**Barangay Staff:**
- ✅ Sees ONLY reports where detected_barangay matches assigned_barangay
- ✅ Sees only their assigned barangay office
- ✅ Hotspot stats show only their barangay data
- ✅ Filter dropdown limited to their barangay

### Files Created

**New Files:**
- `public/js/gis-markers.js` - Marker clustering and filter logic

### Files Modified

**Updated:**
- `resources/views/gis/index.blade.php` - Added Phase 4D UI (hotspot cards, filter panel, recommendation panel)
- `app/Http/Controllers/Api/GISApiController.php` - Added `reports()`, `barangayOffices()`, `hotspotsSummary()` methods
- `routes/api.php` - Added Phase 4D API routes
- `README.md` - Phase 4D documentation

**Preserved:**
- `public/gis/boundary.geojson` - Boundary display still works (Phase 4B)
- GPS detection logic - Still functional (Phase 4C)
- Anonymous reporting - Still maintained

### Testing in Browser

#### Test 1: View GIS Map
1. Login as DILG Admin or Barangay Staff
2. Navigate to GIS Map page
3. Should see:
   - Hotspot summary cards with statistics
   - Filter panel
   - Map with boundaries, report markers, office markers
   - Sidebar with legend and recommendation panel

#### Test 2: Click Report Marker
1. Click any red/orange/green marker on map
2. Popup appears with report details
3. Recommendation panel appears in sidebar
4. Panel shows tracking ID, barangay, and recommended office

#### Test 3: Test Clustering
1. Zoom out on map
2. Nearby markers group into clusters
3. Click cluster
4. Map zooms in to show individual markers

#### Test 4: Apply Filters
1. Select "Bagumbayan" from Barangay dropdown
2. Select "Illegal Parking" from Violation Type dropdown
3. Click "Apply Filters"
4. Only matching markers remain visible
5. Visible count updates

#### Test 5: Reset Filters
1. Click "Reset" button
2. All markers reappear
3. Filter dropdowns clear
4. Visible count shows total

#### Test 6: Role-Based Filtering (Barangay Staff)
1. Login as Barangay Staff (e.g., bagumbayan_staff)
2. Navigate to GIS Map
3. Should ONLY see markers from Bagumbayan
4. Hotspot cards show only Bagumbayan statistics

### Testing API Endpoints

#### Test API 1: Get All Reports
```bash
curl -X GET http://127.0.0.1:8000/api/gis/reports
```

#### Test API 2: Get Filtered Reports
```bash
curl -X GET "http://127.0.0.1:8000/api/gis/reports?barangay=Bagumbayan&status=Submitted"
```

#### Test API 3: Get Barangay Offices
```bash
curl -X GET http://127.0.0.1:8000/api/gis/barangay-offices
```

#### Test API 4: Get Hotspot Summary
```bash
curl -X GET http://127.0.0.1:8000/api/gis/hotspots-summary
```

### What Still Uses Dummy Data

**Using Real Data:**
- ✅ Report markers (from database)
- ✅ GPS coordinates (from database)
- ✅ Hotspot statistics (calculated from database)
- ✅ Barangay boundaries (from GeoJSON)

**Using Demo Data:**
- ⚠️ Barangay office coordinates (should be validated by LGU)
- ⚠️ Test reports (seeded data for demonstration)

**Not Yet Implemented:**
- ❌ Actual mobile app (Phase 6+)
- ❌ Machine Learning / Computer Vision (Phase 5+)
- ❌ Real-time notifications (Phase 6+)

### Important Notes

1. **Barangay office coordinates are demo data**
   - Currently using realistic coordinates near Santa Cruz
   - Marked clearly in code comments
   - Should be validated and updated by the LGU

2. **This is NOT emergency dispatch**
   - Wording: "Recommended Barangay Office for Follow-up"
   - NOT "Emergency Response" or "Emergency Dispatch"
   - This is a road-clearing report system

3. **MarkerCluster plugin via CDN (temporary)**
   - Currently using CDN for rapid Phase 4D implementation
   - TODO: Install via npm and move to local assets
   - Fallback to normal markers if CDN fails

4. **Phase 4B boundaries preserved**
   - Boundary display still works
   - GeoJSON loading unchanged
   - Inverse mask (white overlay) still functional

5. **Phase 4C GPS detection preserved**
   - Point-in-polygon algorithm unchanged
   - Anonymous reporting still works
   - Tracking ID system maintained

### Phase 4D Completion Checklist

- [x] API endpoint: `GET /api/gis/reports`
- [x] API endpoint: `GET /api/gis/barangay-offices`
- [x] API endpoint: `GET /api/gis/hotspots-summary`
- [x] Hotspot summary cards UI
- [x] Filter panel UI with dropdowns
- [x] Apply filters button
- [x] Reset filters button
- [x] Report markers with GPS coordinates
- [x] Marker clustering (Leaflet.markercluster plugin)
- [x] Status-based marker colors
- [x] Report marker popups
- [x] Barangay office markers
- [x] Office marker popups
- [x] Barangay office recommendation panel
- [x] Click marker to show recommendation
- [x] Filter logic (barangay, violation type, status)
- [x] Visible marker count display
- [x] Role-based filtering (DILG vs Barangay Staff)
- [x] Map legend updated
- [x] Demo barangay office coordinates
- [x] Error handling for API calls
- [x] Graceful fallback if clustering fails
- [x] Phase 4B boundaries preserved
- [x] Phase 4C GPS detection preserved
- [x] README.md updated with Phase 4D documentation
- [x] Code comments explaining demo data

### What's Next?

**Phase 5 - Machine Learning & Computer Vision:**
- Automatic violation type detection from images
- Training dataset collection
- ML model integration
- Confidence score calculation
- NOT started yet

**Phase 6 - Mobile App Development:**
- React Native or Flutter mobile app
- Citizen report submission interface
- GPS auto-capture
- Photo capture from camera
- Status tracking by Tracking ID
- NOT started yet

**Phase 7 - Real-Time Notifications:**
- Push notifications to citizens
- SMS alerts to barangay staff
- Email notifications
- NOT started yet

---

## 🗺️ GIS BOUNDARY SETUP

### 📍 Place boundary.geojson File

**Required location:**
```
public/gis/boundary.geojson
```

**What to include:**
- Santa Cruz, Laguna barangay boundary polygons
- GeoJSON format with Feature Collection
- Properties should include barangay name (see supported property keys below)

**Supported barangay name property keys:**
The system intelligently detects barangay names from these GeoJSON properties (in order of priority):
- `name`, `Name`, `NAME`
- `barangay`, `Barangay`, `BARANGAY`
- `brgy`, `Brgy`, `BRGY`
- `BGY_NAME`, `BRGY_NAME`
- `ADM4_EN`, `ADM4_NAME`
- `NAME_4`, `NAME_3`

**Access the GIS Map:**
```
http://127.0.0.1:8000/gis-map
```

**If boundary.geojson is missing:**
- The map will display a friendly warning message
- The page will not crash
- Santa Cruz, Laguna center point will be shown as fallback

**Map Features:**
- ✅ Interactive Leaflet.js map
- ✅ OpenStreetMap base layer
- ✅ Gold boundary outlines (DILG theme)
- ✅ Clickable barangay popups
- ✅ Hover effects on boundaries
- ✅ Auto-fit to boundary layer
- ✅ Map legend
- ✅ Boundary information sidebar
- ✅ Loading indicator
- ✅ Error handling

---

## 🚀 NEXT PHASE

### Phase 4D - Report Markers & Clustering

**Planned Features:**
- 📍 Report markers on GIS map
- 🔍 Report clustering for better readability
- 🔥 Violation hotspot heatmaps
- 🏢 Barangay office markers
- 📊 Office recommendation based on proximity
- 🗺️ Filter reports by type/status on map

**NOT Included in Phase 4:**
- NLP/Machine Learning (Phase 5+)
- Computer Vision (Phase 5+)
- Dataset Training (Phase 5+)
- Real-time Notifications (Phase 6+)
- Public Reporting Portal (Phase 7+)

---

## 🛠️ Installation & Setup

```bash
# Clone repository
git clone <repository-url>
cd DILG-RC

# Install dependencies
composer install
npm install

# Leaflet.js is already installed locally
# No need to download separately - it's in public/js/ and public/css/

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database (includes 27 test accounts)
php artisan db:seed

# Start development server
php artisan serve

# Login credentials (see ACCOUNTS_CREDENTIALS.md)
# DILG Admin: admin@dilg.gov.ph / password
# Barangay Staff: {barangay-slug}@barangay.dilg.gov.ph / password
```

---

## 📚 Documentation Files

- `ACCOUNTS_CREDENTIALS.md` - All 27 test accounts
- `PHASE_3E_COMPLETION_REPORT.md` - Status timeline feature details
- `TESTING_GUIDE.md` - Testing procedures
- `QUICK_ACCESS_GUIDE.md` - Quick reference guide
- `TEST_ALL_LINKS.md` - Link verification results
- `PHASE_4B_COMPLETION_REPORT.md` - GIS boundary integration details
- `LEAFLET_LOCAL_SETUP.md` - Leaflet.js local installation guide

---

## 🎨 UI Theme

**DILG Official Colors:**
- Primary: `#F4C542` (DILG Yellow)
- Secondary: `#D4A017` (DILG Dark Gold)
- Background: `#FFFFFF` (White)
- Text: `#333333` (Dark Gray)
- Success: `#10b981` (Green)
- Warning: `#f59e0b` (Orange)
- Error: `#ef4444` (Red)

**Design Guidelines:**
- Clean, professional government interface
- Notion-style rounded badge buttons
- Font Awesome 6.4.0 icons
- Responsive grid layouts
- Official document formatting for printables
- Times New Roman for printable reports
- Proper page breaks and print CSS

---

## 📄 License

This system is developed for the Department of the Interior and Local Government (DILG) - Santa Cruz, Laguna. Built on Laravel framework which is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
