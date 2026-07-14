<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MobileReportApiController;
use App\Http\Controllers\Api\GISApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*
|--------------------------------------------------------------------------
| PHASE 4A - Mobile App API Foundation
|--------------------------------------------------------------------------
|
| These routes provide the API foundation for the future citizen mobile app.
| Citizens can submit road-clearing violation reports with GPS coordinates.
|
*/

// Public citizen/mobile API routes. Throttles limit anonymous abuse.
Route::prefix('mobile')->group(function () {
    
    // Submit new violation report
    Route::post('/reports', [MobileReportApiController::class, 'store'])->middleware('throttle:10,1');
    
    // Get violation report details
    // Get report status by report_id (e.g., RCV-2026-0001)
    Route::get('/reports/status/{tracking_id}', [MobileReportApiController::class, 'status'])->middleware('throttle:30,1');

    // Numeric report details are staff-only; the public uses the minimal status endpoint.
    Route::get('/reports/{id}', [MobileReportApiController::class, 'show'])
        ->whereNumber('id')
        ->middleware(['web', 'auth', 'throttle:60,1']);
    
    // Get list of violation types
    Route::get('/violation-types', [MobileReportApiController::class, 'violationTypes']);
    
    // Get list of barangays
    Route::get('/barangays', [MobileReportApiController::class, 'barangays']);
});

/*
|--------------------------------------------------------------------------
| Future API Routes (Phase 4B+)
|--------------------------------------------------------------------------
|
| - GIS boundary matching
| - GPS barangay auto-assignment
| - Barangay office clustering
| - Authenticated mobile staff API
|
*/

/*
|--------------------------------------------------------------------------
| PHASE 4C - GPS Barangay Auto-Assignment API
|--------------------------------------------------------------------------
|
| GeoJSON-based barangay boundary detection using point-in-polygon algorithm.
| Automatically assigns reports to the correct barangay based on GPS coordinates.
|
*/

// Public location validation. It returns no report or staff data.
Route::prefix('gis')->group(function () {
    // Detect barangay from GPS coordinates
    Route::post('/detect-barangay', [GISApiController::class, 'detectBarangay'])->middleware('throttle:30,1');
    
    /*
    |--------------------------------------------------------------------------
    | PHASE 4D - GIS Report Markers, Clustering, and Hotspots API
    |--------------------------------------------------------------------------
    |
    | Display violation report markers on GIS map with clustering.
    | Show barangay office locations and hotspot summaries.
    | Recommend correct barangay office for citizen follow-up.
    |
    */
    
    // Get all reports with GPS coordinates for map markers
    Route::middleware(['web', 'auth', 'throttle:60,1'])->group(function () {
        Route::get('/reports', [GISApiController::class, 'reports']);
    
    // Get barangay office locations for map markers
        Route::get('/barangay-offices', [GISApiController::class, 'barangayOffices']);
    
    // Get hotspot summary statistics
        Route::get('/hotspots-summary', [GISApiController::class, 'hotspotsSummary']);
    });
});
