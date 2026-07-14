<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ViolationReport;
use App\Services\BarangayAssignmentService;
use Carbon\Carbon;

class RoadClearingViolationSeeder extends Seeder
{
    /**
     * Seed road clearing violation reports
     */
    public function run(): void
    {
        $violations = [
            [
                'submitted_by' => 'Juan Dela Cruz',
                'contact_number' => '09171234567',
                'description' => 'Large delivery truck illegally parked blocking the entire lane on Main Street. Has been there for 3 days causing heavy traffic congestion.',
                'selected_violation_type' => 'Illegal Parking',
                'latitude' => 14.2800,
                'longitude' => 121.4150,
                'gps_accuracy' => 5.2,
                'status' => 'Submitted',
                'verification_status' => 'Unverified',
                'created_at' => Carbon::now()->subDays(2),
            ],
            [
                'submitted_by' => 'Maria Santos',
                'contact_number' => '09187654321',
                'description' => 'Street vendor cart permanently occupying the entire sidewalk near the market. Pedestrians forced to walk on the road which is very dangerous.',
                'selected_violation_type' => 'Vending Obstruction',
                'latitude' => 14.2850,
                'longitude' => 121.4180,
                'gps_accuracy' => 8.5,
                'status' => 'For Verification',
                'verification_status' => 'Unverified',
                'created_at' => Carbon::now()->subDays(5),
            ],
            [
                'submitted_by' => 'Pedro Reyes',
                'contact_number' => '09171112222',
                'description' => 'Construction materials (sand, cement bags, steel bars) dumped on the road for over a week. Creating obstruction and safety hazard.',
                'selected_violation_type' => 'Construction Materials Obstruction',
                'latitude' => 14.2750,
                'longitude' => 121.4100,
                'gps_accuracy' => 12.0,
                'status' => 'Verified',
                'verification_status' => 'Valid Violation',
                'assigned_personnel' => 'Engr. Ramirez',
                'created_at' => Carbon::now()->subDays(8),
            ],
            [
                'submitted_by' => 'Ana Cruz',
                'contact_number' => '09189998888',
                'description' => 'Abandoned motorcycle on the roadside for more than a month. Becoming an eyesore and safety hazard.',
                'selected_violation_type' => 'Abandoned Vehicle',
                'latitude' => 14.2950,
                'longitude' => 121.4200,
                'gps_accuracy' => 6.8,
                'status' => 'Assigned',
                'verification_status' => 'Valid Violation',
                'assigned_personnel' => 'Tanod Jose',
                'created_at' => Carbon::now()->subDays(15),
            ],
            [
                'submitted_by' => 'Roberto Garcia',
                'contact_number' => '09177773333',
                'description' => "Store extension encroaching 2 meters into the sidewalk. Customers chairs and tables blocking pedestrian path completely.",
                'selected_violation_type' => 'Encroachment',
                'latitude' => 14.3000,
                'longitude' => 121.4230,
                'gps_accuracy' => 4.5,
                'status' => 'In Progress',
                'verification_status' => 'Valid Violation',
                'assigned_personnel' => 'Brgy. Captain Gomez',
                'action_taken' => 'Site inspection conducted. Notice issued to store owner.',
                'created_at' => Carbon::now()->subDays(12),
            ],
            [
                'submitted_by' => 'Lisa Fernandez',
                'contact_number' => '09156667777',
                'description' => 'Garbage bins placed on the road instead of the designated collection area. Blocking motorcycle lane.',
                'selected_violation_type' => 'Waste/Garbage Obstruction',
                'latitude' => 14.2650,
                'longitude' => 121.4050,
                'gps_accuracy' => 15.2,
                'status' => 'Action Taken',
                'verification_status' => 'Valid Violation',
                'assigned_personnel' => 'Waste Management Team',
                'action_taken' => 'Bins relocated to proper collection point. Warning issued to household.',
                'created_at' => Carbon::now()->subDays(20),
            ],
            [
                'submitted_by' => 'Carlos Ramos',
                'contact_number' => '09182223333',
                'description' => 'Informal tricycle terminal operating on main road. Around 15 tricycles parked blocking one full lane during peak hours.',
                'selected_violation_type' => 'Road Obstruction',
                'latitude' => 14.2700,
                'longitude' => 121.4130,
                'gps_accuracy' => 7.3,
                'status' => 'Resolved',
                'verification_status' => 'Valid Violation',
                'assigned_personnel' => 'Traffic Management Office',
                'action_taken' => 'Coordination with tricycle association. Terminal relocated to designated area.',
                'remarks' => 'Successfully resolved. Monitoring for compliance.',
                'created_at' => Carbon::now()->subDays(25),
            ],
            [
                'submitted_by' => 'Elena Martinez',
                'contact_number' => '09195554444',
                'description' => 'Roadside fruit stand structure permanently built on public sidewalk. Metal frame and roof obstructing pedestrian flow.',
                'selected_violation_type' => 'Sidewalk Obstruction',
                'latitude' => 14.3200,
                'longitude' => 121.4310,
                'gps_accuracy' => 9.8,
                'status' => 'Submitted',
                'verification_status' => 'Unverified',
                'created_at' => Carbon::now()->subDays(1),
            ],
            [
                'submitted_by' => 'Miguel Torres',
                'contact_number' => '09171119999',
                'description' => 'Car repair shop using public road as workspace. Car parts, tools, and vehicles under repair occupying the street.',
                'selected_violation_type' => 'Encroachment',
                'latitude' => 14.2600,
                'longitude' => 121.4070,
                'gps_accuracy' => 11.5,
                'status' => 'For Verification',
                'verification_status' => 'Unverified',
                'created_at' => Carbon::now()->subDays(3),
            ],
            [
                'submitted_by' => 'Sofia Aquino',
                'contact_number' => '09188887777',
                'description' => 'Multiple vendors blocking the entire sidewalk during market day. Complete obstruction forcing pedestrians onto dangerous road.',
                'selected_violation_type' => 'Vending Obstruction',
                'latitude' => 14.2400,
                'longitude' => 121.3930,
                'gps_accuracy' => 6.2,
                'status' => 'Verified',
                'verification_status' => 'Valid Violation',
                'assigned_personnel' => 'Market Administrator',
                'created_at' => Carbon::now()->subDays(6),
            ],
        ];

        foreach ($violations as $violationData) {
            // Auto-detect barangay from GPS
            if (isset($violationData['latitude']) && isset($violationData['longitude'])) {
                $barangayData = BarangayAssignmentService::detectBarangay(
                    $violationData['latitude'],
                    $violationData['longitude']
                );
                
                $violationData['detected_barangay'] = $barangayData['detected_barangay'];
                $violationData['assigned_barangay_office'] = $barangayData['assigned_barangay_office'];
                $violationData['location_context'] = 'Within ' . $barangayData['detected_barangay'] . ' boundary';
            }

            // Generate report ID
            $violationData['report_id'] = ViolationReport::generateReportId();
            $violationData['timestamp'] = $violationData['created_at'];
            $violationData['date_submitted'] = $violationData['created_at'];
            
            // Set date_updated for processed reports
            if (in_array($violationData['status'], ['Verified', 'Assigned', 'In Progress', 'Action Taken', 'Resolved'])) {
                $violationData['date_updated'] = Carbon::now()->subDays(rand(1, 5));
            }

            ViolationReport::create($violationData);
        }

        $this->command->info('Road clearing violation data seeded successfully! 10 reports created.');
    }
}
