<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GISController extends Controller
{
    /**
     * Display GIS Boundary Map
     * 
     * Phase 4B - Displays Santa Cruz, Laguna barangay boundaries using boundary.geojson
     * 
     * Future phases:
     * - Phase 4C: GPS-based barangay detection
     * - Phase 4D: Report markers and clustering
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Check if boundary.geojson file exists
        $geojsonPath = public_path('gis/boundary.geojson');
        $geojsonExists = file_exists($geojsonPath);
        
        // GeoJSON URL for frontend
        $geojsonUrl = asset('gis/boundary.geojson');
        
        // Get user information for role-based layout
        $user = auth()->user();
        $isDilgAdmin = $user && $user->role === 'dilg_admin';
        
        // Get barangay list
        $barangays = config('santa_cruz_barangays.barangays', []);
        $barangayCount = count($barangays);
        
        // Santa Cruz, Laguna coordinates (fallback center)
        $defaultCenter = [
            'lat' => 14.2833,
            'lng' => 121.4167
        ];
        
        // Pass data to view
        return view('gis.index', [
            'geojsonExists' => $geojsonExists,
            'geojsonUrl' => $geojsonUrl,
            'isDilgAdmin' => $isDilgAdmin,
            'barangayCount' => $barangayCount,
            'defaultCenter' => $defaultCenter,
        ]);
    }
}
