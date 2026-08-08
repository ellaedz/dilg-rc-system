# Leaflet.js Local Installation Guide
## DILG-RC GIS Map - Production Ready Setup

---

## ✅ LEAFLET IS NOW LOCAL

Your system now uses **locally downloaded Leaflet.js** instead of CDN. This means:
- ✅ **Works offline** (no internet required for Leaflet library)
- ✅ **Production ready** (no external dependencies)
- ✅ **Faster loading** (served from your local server)
- ✅ **More reliable** (no CDN downtime issues)

---

## 📂 INSTALLED FILES

### Leaflet Library Files (Installed)
```
public/js/leaflet.js              ← Main JavaScript library
public/css/leaflet.css            ← CSS styles
public/css/images/                ← Marker icons and UI images
  ├── layers.png
  ├── layers-2x.png
  ├── marker-icon.png
  ├── marker-icon-2x.png
  └── marker-shadow.png
```

### GeoJSON Boundary File (Installed)
```
public/gis/boundary.geojson       ← Santa Cruz, Laguna barangay boundaries
```

---

## 📦 INSTALLATION METHOD

### How It Was Installed
```bash
# 1. Install Leaflet via npm
npm install leaflet --save

# 2. Copy Leaflet files to public directory
xcopy /E /I node_modules\leaflet\dist\images public\css\images
copy node_modules\leaflet\dist\leaflet.css public\css\leaflet.css
copy node_modules\leaflet\dist\leaflet.js public\js\leaflet.js

# 3. Copy GeoJSON boundary file
copy "Santa Cruz Boundary.geojson" public\gis\boundary.geojson
```

### Package.json Entry
```json
{
  "dependencies": {
    "leaflet": "^1.9.4"
  }
}
```

---

## 🌐 INTERNET REQUIREMENTS

### ✅ What Works Offline
- Leaflet.js library (local file)
- Leaflet CSS (local file)
- Leaflet icons and images (local files)
- GeoJSON boundary loading (local file)
- Map interactions (zoom, pan, click)
- Barangay popups

### ⚠️ What Requires Internet
- **OpenStreetMap tiles** (base map imagery)
  - The actual map background images come from OpenStreetMap servers
  - Without internet, you'll see a gray map with boundaries but no street details
  - Alternative: Can download and host tiles locally (advanced setup)

---

## 🗺️ GIS VIEW UPDATE

### Before (CDN)
```html
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
```

### After (Local)
```html
<!-- Leaflet CSS (Local) -->
<link rel="stylesheet" href="{{ asset('css/leaflet.css') }}" />

<!-- Leaflet JS (Local) -->
<script src="{{ asset('js/leaflet.js') }}"></script>
```

---

## 🚀 DEPLOYMENT NOTES

### For Production Server
When deploying to production server:

1. **Include These Directories:**
   ```
   public/js/
   public/css/
   public/gis/
   ```

2. **Run npm install (Optional):**
   - Not required if you already copied files to public/
   - Only needed if you want to update Leaflet version later

3. **File Permissions:**
   - Ensure web server can read public/ directory
   - Set proper permissions: `chmod -R 755 public/`

4. **Web Server Configuration:**
   - No special configuration needed
   - Laravel's `asset()` helper will generate correct URLs

---

## 🔄 UPDATING LEAFLET

### To Update Leaflet Version Later
```bash
# 1. Update package.json
npm update leaflet

# 2. Re-copy files to public
copy node_modules\leaflet\dist\leaflet.css public\css\leaflet.css
copy node_modules\leaflet\dist\leaflet.js public\js\leaflet.js
xcopy /E /I /Y node_modules\leaflet\dist\images public\css\images

# 3. Clear browser cache
php artisan cache:clear
```

---

## 📍 GEOJSON FILE

### Boundary GeoJSON Location
```
public/gis/boundary.geojson
```

### GeoJSON Format
- **Type:** FeatureCollection
- **Features:** 1 MultiPolygon (Santa Cruz, Laguna boundary)
- **Properties Detected:**
  - `ADM3_EN`: "Santa Cruz (Capital)"
  - `ADM2_EN`: "Laguna"
  - `ADM1_EN`: "Region IV-A (Calabarzon)"
  - `ADM0_EN`: "Philippines (the)"

### Supported Property Keys
Your GeoJSON uses `ADM3_EN` which is detected by the system. The map will automatically show "Santa Cruz (Capital)" in popups.

---

## 🧪 TESTING

### Test 1: Verify Local Files
```bash
# Check if files exist
dir public\js\leaflet.js
dir public\css\leaflet.css
dir public\css\images
dir public\gis\boundary.geojson
```

### Test 2: Open GIS Map
```
1. Start server: php artisan serve
2. Login: admin@dilg.gov.ph / password
3. Click "GIS Map" in sidebar
4. Expected: Map loads with Santa Cruz boundary visible
```

### Test 3: Check Browser Console
```
1. Press F12 to open Developer Tools
2. Go to Console tab
3. Expected: "✅ GeoJSON loaded successfully"
4. No 404 errors for Leaflet files
```

### Test 4: Verify Network Tab
```
1. Open Developer Tools → Network tab
2. Refresh /gis-map page
3. Expected:
   ✅ leaflet.js loaded from your domain (not unpkg.com)
   ✅ leaflet.css loaded from your domain
   ✅ boundary.geojson loaded successfully
   ✅ OpenStreetMap tiles loaded (if internet available)
```

---

## 🎯 FILE SIZES

### Leaflet Files
- `leaflet.js`: ~150 KB
- `leaflet.css`: ~15 KB
- `images/*.png`: ~20 KB total

### GeoJSON File
- `boundary.geojson`: ~50 KB (Santa Cruz boundary polygon)

**Total Local Storage:** ~235 KB (very small)

---

## ⚙️ ALTERNATIVE: OFFLINE MAP TILES

### If You Need Fully Offline GIS (Optional)
For completely offline operation (no internet for tiles):

**Option 1: Use Local Tile Server**
- Download OpenStreetMap tiles for Santa Cruz, Laguna area
- Use TileServer-GL or similar tool
- Update Leaflet tile layer URL to local server

**Option 2: Use Static Map Image**
- Create static PNG of Santa Cruz, Laguna
- Use as background instead of tile layer
- Boundaries will still be interactive

**Option 3: Use MBTiles**
- Download offline map tiles as .mbtiles file
- Use TileServer to serve locally
- Requires additional setup

**Note:** For Phase 4B, OpenStreetMap tiles via internet is acceptable. Offline tiles can be implemented in future if needed.

---

## ✅ VERIFICATION CHECKLIST

### Installation Complete When:
- ✅ `public/js/leaflet.js` exists
- ✅ `public/css/leaflet.css` exists
- ✅ `public/css/images/` contains 5 PNG files
- ✅ `public/gis/boundary.geojson` exists
- ✅ GIS view updated to use `asset()` helper
- ✅ Browser console shows no 404 errors
- ✅ Map displays Santa Cruz boundary correctly

---

## 📝 TROUBLESHOOTING

### Problem: Map doesn't load
**Solution:**
1. Check if Leaflet files exist in public/
2. Check browser console for errors
3. Verify Laravel asset URLs are correct
4. Clear browser cache: Ctrl+Shift+Delete

### Problem: Boundaries don't show
**Solution:**
1. Check if boundary.geojson exists
2. Check browser console for GeoJSON load errors
3. Verify GeoJSON is valid JSON format
4. Check file permissions

### Problem: Icons/images missing
**Solution:**
1. Check if public/css/images/ directory exists
2. Verify all 5 PNG files are present
3. Check CSS file references correct image path

---

## 🎉 SUMMARY

### What Changed
- ❌ **Before:** Leaflet loaded from CDN (internet required)
- ✅ **After:** Leaflet loaded locally (works offline)

### Benefits
- ✅ Production ready
- ✅ No external dependencies for library
- ✅ Faster loading times
- ✅ More reliable
- ✅ Works in local/intranet environments

### Internet Still Required For
- OpenStreetMap tile imagery (base map background)
- Can be made fully offline with additional tile server setup

---

**Status:** ✅ Leaflet successfully installed locally  
**Date:** June 26, 2026  
**Version:** Leaflet 1.9.4  
**Installation Method:** npm + manual copy to public/  

---
