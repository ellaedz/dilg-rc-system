# 🧪 API TESTING GUIDE - PHASE 4C

## Quick Reference for Postman Testing

---

## 📍 1. DETECT BARANGAY FROM GPS

**Test GPS-based barangay detection before submitting a report**

### Request

```
Method: POST
URL: http://127.0.0.1:8000/api/gis/detect-barangay
Headers: Content-Type: application/json
```

### Body (JSON)

```json
{
  "latitude": 14.2819,
  "longitude": 121.4166
}
```

### Expected Response (Inside Coverage)

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

### Test Cases

**Test 1: Inside Santa Cruz (Bagumbayan area)**
```json
{ "latitude": 14.2819, "longitude": 121.4166 }
```

**Test 2: Outside Santa Cruz**
```json
{ "latitude": 15.0000, "longitude": 122.0000 }
```
Expected: "Outside Santa Cruz Coverage"

**Test 3: Missing Latitude**
```json
{ "longitude": 121.4166 }
```
Expected: 422 Validation Error

---

## 📱 2. SUBMIT ANONYMOUS REPORT

**Submit a road clearing violation report with GPS auto-assignment**

### Request

```
Method: POST
URL: http://127.0.0.1:8000/api/mobile/reports
Headers: Content-Type: multipart/form-data
```

### Body (Form Data)

```
description: Large delivery truck illegally parked blocking the entire lane on Main Street. Has been there for 3 days causing heavy traffic congestion.

selected_violation_type: Illegal Parking

latitude: 14.2819

longitude: 121.4166

timestamp: 2026-07-06T10:30:00Z

image: [Select File - JPG/PNG, max 5MB]

contact_number: 09171234567

gps_accuracy: 5.2
```

### Required Fields

✅ `description` - String, report details  
✅ `selected_violation_type` - One of:
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

✅ `latitude` - Number, -90 to 90  
✅ `longitude` - Number, -180 to 180  
✅ `timestamp` - ISO 8601 datetime  
✅ `image` - Image file (JPG/PNG/WEBP, max 5MB)

### Optional Fields

⚪ `contact_number` - String, for follow-up  
⚪ `gps_accuracy` - Number, GPS accuracy in meters

### Expected Response

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

### ⚠️ Save the Tracking ID!

The `tracking_id` (same as `report_id`) is the ONLY way to check report status later!

---

## 🔍 3. CHECK REPORT STATUS

**Track report status using Tracking ID (Anonymous)**

### Request

```
Method: GET
URL: http://127.0.0.1:8000/api/mobile/reports/status/{tracking_id}
```

### Example

```
GET http://127.0.0.1:8000/api/mobile/reports/status/RCV-2026-0001
```

### Expected Response

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

### Status Values

- `Submitted` - Report received
- `For Verification` - Under review
- `Verified` - Confirmed valid violation
- `Rejected` - Not a valid violation
- `Assigned` - Personnel assigned
- `In Progress` - Action being taken
- `Action Taken` - Enforcement completed
- `Resolved` - Violation cleared
- `Closed` - Case closed

### Privacy Note

❌ This endpoint does NOT expose:
- Citizen name
- Email address
- Home address
- Contact number

Only the Tracking ID is needed!

---

## 📋 4. GET VIOLATION TYPES

**Get list of available violation types**

### Request

```
Method: GET
URL: http://127.0.0.1:8000/api/mobile/violation-types
```

### Expected Response

```json
{
  "success": true,
  "message": "Violation types retrieved successfully",
  "data": {
    "violation_types": [
      "Illegal Parking",
      "Road Obstruction",
      "Sidewalk Obstruction",
      "Vending Obstruction",
      "Construction Materials Obstruction",
      "Encroachment",
      "Abandoned Vehicle",
      "Illegal Structure",
      "Waste/Garbage Obstruction",
      "Other Road Clearing Violation"
    ]
  }
}
```

---

## 🏘️ 5. GET BARANGAY LIST

**Get list of Santa Cruz barangays**

### Request

```
Method: GET
URL: http://127.0.0.1:8000/api/mobile/barangays
```

### Expected Response

```json
{
  "success": true,
  "message": "Barangays retrieved successfully",
  "data": {
    "barangays": [
      {
        "name": "Alipit",
        "office": "Barangay Hall - Alipit",
        "center_lat": 14.2750,
        "center_lon": 121.4100
      },
      {
        "name": "Bagumbayan",
        "office": "Barangay Hall - Bagumbayan",
        "center_lat": 14.2819,
        "center_lon": 121.4166
      }
      // ... 24 more barangays
    ],
    "total": 26
  }
}
```

---

## 🧪 POSTMAN COLLECTION SETUP

### Step 1: Create Collection

1. Open Postman
2. Create New Collection: "DILG-RC API - Phase 4C"
3. Add Environment: "DILG-RC Local"
4. Set variable: `base_url` = `http://127.0.0.1:8000`

### Step 2: Add Requests

Add these 5 requests to your collection:

1. **Detect Barangay** - POST `{{base_url}}/api/gis/detect-barangay`
2. **Submit Report** - POST `{{base_url}}/api/mobile/reports`
3. **Check Status** - GET `{{base_url}}/api/mobile/reports/status/RCV-2026-0001`
4. **Get Violation Types** - GET `{{base_url}}/api/mobile/violation-types`
5. **Get Barangays** - GET `{{base_url}}/api/mobile/barangays`

### Step 3: Test Flow

1. **First** → Get Violation Types (to see options)
2. **Second** → Detect Barangay (test GPS)
3. **Third** → Submit Report (creates new report)
4. **Fourth** → Check Status (using returned tracking_id)

---

## ⚠️ COMMON ERRORS & SOLUTIONS

### Error 1: "The latitude field is required"

**Cause:** Missing latitude in request  
**Solution:** Add `"latitude": 14.2819` to request body

### Error 2: "The selected violation type is invalid"

**Cause:** Violation type doesn't match allowed values  
**Solution:** Use GET /api/mobile/violation-types to see valid options

### Error 3: "Report not found"

**Cause:** Tracking ID doesn't exist  
**Solution:** Check the tracking_id from submit response

### Error 4: "The image must be an image"

**Cause:** Uploaded file is not JPG/PNG/WEBP  
**Solution:** Use valid image format

### Error 5: 500 Server Error

**Cause:** GeoJSON file missing or Laravel error  
**Solution:** 
- Check `public/gis/boundary.geojson` exists
- Check Laravel logs: `storage/logs/laravel.log`
- Run: `php artisan config:clear`

---

## 📊 TESTING CHECKLIST

### Basic Tests

- [ ] Detect barangay with valid GPS (inside Santa Cruz)
- [ ] Detect barangay with invalid GPS (outside Santa Cruz)
- [ ] Submit report with all required fields
- [ ] Submit report without image (should fail)
- [ ] Submit report without GPS (should fail)
- [ ] Check status with valid tracking ID
- [ ] Check status with invalid tracking ID
- [ ] Get violation types list
- [ ] Get barangays list

### Edge Cases

- [ ] GPS exactly on barangay border
- [ ] GPS with high accuracy value (e.g., 100 meters)
- [ ] Very long description text
- [ ] Large image file (near 5MB limit)
- [ ] Missing optional fields (should still work)
- [ ] Invalid violation type
- [ ] Invalid timestamp format

### Privacy Tests

- [ ] Status API doesn't expose citizen name
- [ ] Status API doesn't expose contact number
- [ ] Status API doesn't expose address
- [ ] Only tracking ID is needed for status check

---

## 🎯 SUCCESS CRITERIA

### Phase 4C is working correctly if:

✅ GPS coordinates correctly detect barangay name  
✅ Point-in-polygon returns accurate results  
✅ Reports auto-assign to correct barangay  
✅ Outside coverage is properly handled  
✅ Missing GPS is properly handled  
✅ Tracking ID system works  
✅ Anonymous reporting maintained  
✅ Status API doesn't expose identity  
✅ All validation rules work  
✅ Error responses are clear

---

## 📝 NOTES FOR MOBILE APP DEVELOPERS

### When building the mobile app:

1. **Capture GPS automatically** - Don't ask user to type coordinates
2. **Save Tracking ID** - Store locally, display prominently
3. **Show barangay detection result** - Let user know which office handles their report
4. **Handle GPS permissions** - Request location access properly
5. **Photo quality** - Compress images before upload to stay under 5MB
6. **Offline support** - Queue reports when internet unavailable
7. **Privacy first** - Never ask for name/email/address
8. **Status updates** - Allow checking status without login

### Recommended Flow:

```
1. Open app (no login required)
2. Take photo of violation
3. App captures GPS automatically
4. User selects violation type
5. User writes description
6. [Optional] User provides contact number
7. Submit → Receive Tracking ID
8. Save Tracking ID to device
9. Check status anytime with Tracking ID
```

---

## 🚀 READY FOR PHASE 4D!

All API endpoints tested and working! The mobile app foundation is ready. Next phase will add report markers and clustering to the GIS map.

