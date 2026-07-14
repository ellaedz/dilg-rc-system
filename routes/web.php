<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DilgDashboardController;
use App\Http\Controllers\BarangayDashboardController;
use App\Http\Controllers\ViolationReportController;
use App\Http\Controllers\BarangayIncomingReportController;
use App\Http\Controllers\BarangayVerifiedReportController;
use App\Http\Controllers\BarangayResponseTrackingController;
use App\Http\Controllers\ResponseTrackingController;
use App\Http\Controllers\BarangayPerformanceController;
use App\Http\Controllers\AnalyticsReportController;
use App\Http\Controllers\BarangayAnalyticsReportController;
use App\Http\Controllers\AIController;
use App\Http\Controllers\GISController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ManualBarangayRoutingController;

// ========================================
// PUBLIC ROUTES (No Authentication)
// ========================================

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

// ========================================
// AUTHENTICATED ROUTES
// ========================================

Route::middleware(['auth'])->group(function () {
    
    // Logout Route
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ========================================
    // DILG ADMIN ROUTES (dilg_admin role only)
    // ========================================
    
    Route::middleware(['dilg.admin'])->group(function () {
        
        // DILG Admin Dashboard
        Route::get('/dilg-dashboard', [DilgDashboardController::class, 'index'])->name('dilg.dashboard');
        
        // All Violation Reports (DILG Admin View Only - READ ONLY MONITORING)
        Route::get('/violation-reports', [ViolationReportController::class, 'index'])->name('violation-reports.index');
        
        // Barangay Performance (DILG Admin View)
        Route::get('/barangay-performance', [BarangayPerformanceController::class, 'index'])->name('barangay-performance.index');
        
        // DILG Response Tracking
        Route::get('/response-tracking', [ResponseTrackingController::class, 'index'])->name('response-tracking.index');
        
        // DILG Analytics & Reports
        Route::get('/analytics-reports', [AnalyticsReportController::class, 'index'])->name('analytics-reports.index');
        Route::get('/analytics-reports/export', [AnalyticsReportController::class, 'export'])->name('analytics-reports.export');
        Route::get('/analytics-reports/print', [AnalyticsReportController::class, 'print'])->name('analytics-reports.print');
        
        // AI Analytics Routes (Placeholder for Phase 4)
        Route::get('/ai-analytics', function() {
            return view('ai-analytics.index');
        })->name('ai.index');
        
        // GIS Map Routes (Phase 4B - Barangay Boundary Integration)
        Route::get('/gis-map', [GISController::class, 'index'])->name('gis.index');

        Route::get('/needs-barangay-review', [ManualBarangayRoutingController::class, 'index'])
            ->name('dilg.needs-barangay-review.index');
        Route::post('/needs-barangay-review/{report}/route', [ManualBarangayRoutingController::class, 'route'])
            ->name('dilg.needs-barangay-review.route');
        
        // Profile Route (Placeholder)
        Route::get('/profile', function() {
            return view('profile.index');
        })->name('profile');
        
        // Original Dashboard (keep for backward compatibility)
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

    // ========================================
    // BARANGAY STAFF ROUTES (barangay_staff role only)
    // ========================================
    
    Route::middleware(['barangay.staff'])->group(function () {
        
        // Barangay Dashboard
        Route::get('/barangay/{barangay}/dashboard', [BarangayDashboardController::class, 'index'])->name('barangay.dashboard');
        
        // Barangay Incoming Reports
        Route::get('/barangay/{barangay}/incoming-reports', [BarangayIncomingReportController::class, 'index'])->name('barangay.incoming-reports');
        Route::post('/barangay/{barangay}/incoming-reports/{report}/verify', [BarangayIncomingReportController::class, 'verify'])->name('barangay.incoming-reports.verify');
        Route::post('/barangay/{barangay}/incoming-reports/{report}/reject', [BarangayIncomingReportController::class, 'reject'])->name('barangay.incoming-reports.reject');
        
        // Barangay Verified Reports
        Route::get('/barangay/{barangay}/verified-reports', [BarangayVerifiedReportController::class, 'index'])->name('barangay.verified-reports');
        Route::post('/barangay/{barangay}/verified-reports/{report}/assign', [BarangayVerifiedReportController::class, 'assign'])->name('barangay.verified-reports.assign');
        
        // Barangay Response Tracking
        Route::get('/barangay/{barangay}/response-tracking', [BarangayResponseTrackingController::class, 'index'])->name('barangay.response-tracking');
        
        // Barangay Report Update (from report detail view)
        Route::put('/barangay/{barangay}/reports/{report}', [BarangayResponseTrackingController::class, 'update'])->name('barangay.report.update');
        
        // Barangay Analytics
        Route::get('/barangay/{barangay}/analytics-reports', [BarangayAnalyticsReportController::class, 'index'])->name('barangay.analytics-reports');
        Route::get('/barangay/{barangay}/analytics-reports/print', [BarangayAnalyticsReportController::class, 'print'])->name('barangay.analytics-reports.print');
        
        // Barangay Profile
        Route::get('/barangay/{barangay}/profile', function($barangay) {
            return view('profile.index', compact('barangay'));
        })->name('barangay.profile');
    });

    // ========================================
    // SHARED ROUTES (Both roles can access with data filtering)
    // ========================================
    
    // View Individual Violation Report (role-based layout detection)
    Route::get('/violation-reports/{violationReport}', [ViolationReportController::class, 'show'])->name('violation-reports.show');
});

// Local admin diagnostics only. Never expose framework/runtime details publicly.
if (app()->environment('local')) {
    Route::middleware(['auth', 'dilg.admin'])->get('/test', function () {
        return response()->json([
            'message' => 'DILG-RC System - Phase 3C: Authentication & Role-Based Access',
            'architecture' => 'Role-Based Authentication',
            'roles' => [
                'dilg_admin' => 'Can access all barangays and DILG-wide analytics',
                'barangay_staff' => 'Can only access their assigned barangay'
            ],
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'database' => config('database.default'),
            'barangays_count' => count(config('santa_cruz_barangays.barangays', [])),
            'status' => 'Phase 3C - Authentication Complete'
        ]);
    })->name('diagnostics.test');
}

// ========================================
// AJAX API ROUTES (Real-time Updates)
// ========================================

Route::middleware(['auth'])->prefix('api')->group(function () {
    // DILG Dashboard Stats
    Route::get('/dilg-dashboard-stats', [DilgDashboardController::class, 'getStats'])->middleware('dilg.admin');
    
    // All Violation Reports Updates
    Route::get('/violation-reports-updates', [ViolationReportController::class, 'getUpdates'])->middleware('dilg.admin');
    
    // Barangay Dashboard Stats
    Route::get('/barangay/{barangay}/dashboard-stats', [BarangayDashboardController::class, 'getStats'])->middleware('barangay.staff');
    
    // Barangay Incoming Reports Updates
    Route::get('/barangay/{barangay}/incoming-reports-updates', [BarangayIncomingReportController::class, 'getUpdates'])->middleware('barangay.staff');
});
