<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Record;
use App\Models\Complaint;

class RecordSeeder extends Seeder
{
    public function run(): void
    {
        // Sample Records for Santa Cruz, Laguna
        $records = [
            [
                'record_id' => 'REC-2026-001',
                'full_name' => 'Juan Dela Cruz',
                'contact_number' => '09123456789',
                'address' => 'Block 5 Lot 12, Villa Hermosa Subdivision, Barangay Oogong, Santa Cruz, Laguna',
                'concern_type' => 'Request',
                'description' => 'Request for infrastructure development project approval for barangay basketball court renovation.',
                'date_submitted' => now()->subDays(5),
                'status' => 'In Progress',
                'assigned_office' => 'DILG Regional Office IV-A',
                'assigned_personnel' => 'Maria Santos',
                'remarks' => 'Initial review completed. Awaiting budget allocation approval.'
            ],
            [
                'record_id' => 'REC-2026-002',
                'full_name' => 'Pedro Reyes',
                'contact_number' => '09234567890',
                'address' => '123 Rizal Street, Barangay Poblacion I, Santa Cruz, Laguna',
                'concern_type' => 'Complaint',
                'description' => 'Complaint regarding delayed barangay clearance processing. Application submitted 2 months ago but still pending.',
                'date_submitted' => now()->subDays(3),
                'status' => 'Pending',
                'assigned_office' => null,
                'assigned_personnel' => null,
                'remarks' => null
            ],
            [
                'record_id' => 'REC-2026-003',
                'full_name' => 'Ana Garcia',
                'contact_number' => '09345678901',
                'address' => 'Purok 3, Barangay Pagsawitan, Santa Cruz, Laguna',
                'concern_type' => 'Report',
                'description' => 'Monthly barangay development report submission for Q2 2026.',
                'date_submitted' => now()->subDays(10),
                'status' => 'Resolved',
                'assigned_office' => 'Municipal Planning Office',
                'assigned_personnel' => 'Roberto Mendoza',
                'remarks' => 'Report reviewed and approved. Recommendations forwarded to regional office.'
            ]
        ];

        foreach ($records as $record) {
            Record::create($record);
        }

        // Sample Complaints for Santa Cruz, Laguna
        $complaints = [
            [
                'complaint_id' => 'CMP-2026-001',
                'full_name' => 'Maria Santos',
                'contact_number' => '09456789012',
                'address' => '45 JP Rizal Street',
                'barangay' => 'Barangay Poblacion II',
                'municipality' => 'Santa Cruz',
                'province' => 'Laguna',
                'concern_type' => 'Complaint',
                'subject' => 'Road damage affecting local transportation',
                'description' => 'The main road along Poblacion II has multiple large potholes that pose danger to motorists and pedestrians. Several accidents have occurred in the past month.',
                'priority' => 'High',
                'status' => 'In Progress',
                'assigned_office' => 'DPWH Laguna District Office',
                'assigned_personnel' => 'Engr. Jose Cruz',
                'remarks' => 'Site inspection scheduled for next week.',
                'date_filed' => now()->subDays(7)
            ],
            [
                'complaint_id' => 'CMP-2026-002',
                'full_name' => 'Roberto Mendoza',
                'contact_number' => '09567890123',
                'address' => 'Zone 2, Phase 3, Villa Hermosa',
                'barangay' => 'Barangay Oogong',
                'municipality' => 'Santa Cruz',
                'province' => 'Laguna',
                'concern_type' => 'Request',
                'subject' => 'Request for additional street lights',
                'description' => 'Our subdivision needs more street lights for safety and security purposes. Currently only 3 functioning lights for 20 households.',
                'priority' => 'Medium',
                'status' => 'Pending',
                'assigned_office' => null,
                'assigned_personnel' => null,
                'remarks' => null,
                'date_filed' => now()->subDays(2)
            ],
            [
                'complaint_id' => 'CMP-2026-003',
                'full_name' => 'Elena Francisco',
                'contact_number' => '09678901234',
                'address' => '78 Sunset Avenue',
                'barangay' => 'Barangay Pagsawitan',
                'municipality' => 'Santa Cruz',
                'province' => 'Laguna',
                'concern_type' => 'Complaint',
                'subject' => 'Water supply interruption',
                'description' => 'No running water for the past 5 days in our area. Barangay officials have not provided any updates or alternative water source.',
                'priority' => 'High',
                'status' => 'Referred',
                'assigned_office' => 'Santa Cruz Water District',
                'assigned_personnel' => 'Atty. Carmen Lopez',
                'remarks' => 'Referred to Water District for immediate action.',
                'date_filed' => now()->subDays(5)
            ]
        ];

        foreach ($complaints as $complaint) {
            Complaint::create($complaint);
        }
    }
}
