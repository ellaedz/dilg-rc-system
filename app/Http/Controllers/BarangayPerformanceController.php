<?php

namespace App\Http\Controllers;

use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarangayPerformanceController extends Controller
{
    /**
     * DILG Admin View - Barangay Performance Rankings
     */
    public function index()
    {
        // Get performance metrics for all barangays
        $barangayPerformance = ViolationReport::selectRaw('
                COALESCE(detected_barangay, manually_assigned_barangay) as detected_barangay,
                COUNT(*) as total_reports,
                SUM(CASE WHEN status = "Resolved" THEN 1 ELSE 0 END) as resolved_count,
                SUM(CASE WHEN status IN ("Submitted", "For Verification", "Verified", "Assigned", "In Progress") THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN verification_status = "Valid Violation" THEN 1 ELSE 0 END) as verified_valid_count,
                AVG(CASE WHEN response_time_hours IS NOT NULL THEN response_time_hours ELSE NULL END) as avg_response_time,
                ROUND(SUM(CASE WHEN status = "Resolved" THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as resolution_rate,
                ROUND(SUM(CASE WHEN verification_status = "Valid Violation" THEN 1 ELSE 0 END) * 100.0 / NULLIF(SUM(CASE WHEN verification_status IS NOT NULL AND verification_status != "Unverified" THEN 1 ELSE 0 END), 0), 2) as verification_completion_rate
            ')
            ->where(function ($query) {
                $query->whereNotNull('detected_barangay')->orWhereNotNull('manually_assigned_barangay');
            })
            ->groupByRaw('COALESCE(detected_barangay, manually_assigned_barangay)')
            ->get();

        // Calculate performance score
        $barangayPerformance = $barangayPerformance->map(function ($barangay) {
            // Performance formula:
            // Score = (resolution_rate * 0.5) + (response_time_score * 0.3) + (verification_completion * 0.2)
            
            $resolutionScore = $barangay->resolution_rate ?? 0;
            
            // Response time score (inverse - lower is better, max 48 hours = 0 score)
            $responseTimeScore = 0;
            if ($barangay->avg_response_time) {
                $responseTimeScore = max(0, 100 - ($barangay->avg_response_time / 48 * 100));
            }
            
            $verificationScore = $barangay->verification_completion_rate ?? 0;
            
            $performanceScore = ($resolutionScore * 0.5) + ($responseTimeScore * 0.3) + ($verificationScore * 0.2);
            
            $barangay->performance_score = round($performanceScore, 2);
            $barangay->avg_response_time = $barangay->avg_response_time 
                ? round($barangay->avg_response_time, 1) . ' hrs' 
                : 'N/A';
            
            return $barangay;
        });

        // Sort by performance score
        $barangayPerformance = $barangayPerformance->sortByDesc('performance_score')->values();

        // Add ranking
        $barangayPerformance = $barangayPerformance->map(function ($barangay, $index) {
            $barangay->rank = $index + 1;
            return $barangay;
        });

        // Identify top performer and needs attention
        $topPerformer = $barangayPerformance->first();
        $needsAttention = $barangayPerformance->last();

        return view('dilg.barangay-performance', compact('barangayPerformance', 'topPerformer', 'needsAttention'));
    }
}
