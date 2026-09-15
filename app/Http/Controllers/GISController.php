<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class GISController extends Controller
{
    /**
     * Display the Santa Cruz GIS monitoring map.
     */
    public function index(Request $request): View
    {
        $municipalGeojsonPath = public_path('gis/santa_cruz_municipality.geojson');
        $barangayGeojsonPath = public_path('gis/santa_cruz_barangays.geojson');

        // Get user information for role-based layout
        $user = auth()->user();
        $isDilgAdmin = $user && $user->role === 'dilg_admin';
        $mapScopeBarangay = $isDilgAdmin ? null : $user?->assigned_barangay;

        // Get barangay list
        $barangays = config('santa_cruz_barangays.barangays', []);
        $barangayCount = count($barangays);

        // Santa Cruz, Laguna coordinates (fallback center)
        $defaultCenter = [
            'lat' => 14.2833,
            'lng' => 121.4167,
        ];

        // Pass data to view
        return view('gis.index', [
            'municipalGeojsonExists' => file_exists($municipalGeojsonPath),
            'municipalGeojsonUrl' => asset('gis/santa_cruz_municipality.geojson'),
            'barangayGeojsonExists' => file_exists($barangayGeojsonPath),
            'barangayGeojsonUrl' => asset('gis/santa_cruz_barangays.geojson'),
            'isDilgAdmin' => $isDilgAdmin,
            'mapScopeBarangay' => $mapScopeBarangay,
            'barangayCount' => $barangayCount,
            'defaultCenter' => $defaultCenter,
        ]);
    }
}
