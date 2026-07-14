<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index()
    {
        // Dummy user profile data
        $user = [
            'name' => 'Admin User',
            'email' => 'admin@dilg-rc.gov.ph',
            'position' => 'Regional Coordinator',
            'office' => 'DILG Regional Office - Region IV-A (CALABARZON)',
            'municipality' => 'Santa Cruz, Laguna',
            'phone' => '+63 912 345 6789',
            'joined_date' => '2023-01-15',
            'employee_id' => 'DILG-2023-001'
        ];

        return view('profile', compact('user'));
    }
}
