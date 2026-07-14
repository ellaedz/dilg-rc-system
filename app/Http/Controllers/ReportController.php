<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        // Reports data for Santa Cruz, Laguna only
        $reports = [
            [
                'id' => 'RPT-2024-001',
                'title' => 'Monthly Performance Report - May 2024',
                'type' => 'Performance Report',
                'barangay' => 'All Barangays',
                'generated_by' => 'Admin User',
                'status' => 'Completed',
                'date_generated' => '2024-06-01'
            ],
            [
                'id' => 'RPT-2024-002',
                'title' => 'Barangay Development Summary - Poblacion II',
                'type' => 'Development Report',
                'barangay' => 'Poblacion II',
                'generated_by' => 'Regional Coordinator',
                'status' => 'Completed',
                'date_generated' => '2024-06-05'
            ],
            [
                'id' => 'RPT-2024-003',
                'title' => 'Complaints Analysis Report - June 2024',
                'type' => 'Complaints Report',
                'barangay' => 'All Barangays',
                'generated_by' => 'System Administrator',
                'status' => 'In Progress',
                'date_generated' => '2024-06-10'
            ],
            [
                'id' => 'RPT-2024-004',
                'title' => 'Budget Utilization Report Q1 2024',
                'type' => 'Financial Report',
                'barangay' => 'Oogong',
                'generated_by' => 'Finance Officer',
                'status' => 'Completed',
                'date_generated' => '2024-04-15'
            ],
            [
                'id' => 'RPT-2024-005',
                'title' => 'Infrastructure Projects Status - Pagsawitan',
                'type' => 'Project Report',
                'barangay' => 'Pagsawitan',
                'generated_by' => 'Project Manager',
                'status' => 'Pending',
                'date_generated' => '2024-06-08'
            ]
        ];

        return view('reports.index', compact('reports'));
    }
}
