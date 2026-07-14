<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Record;
use App\Services\BarangayAssignmentService;
use Carbon\Carbon;

class Phase2ConcernSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Realistic concern data with GPS coordinates
        $concerns = [
            [
                'full_name' => 'Juan Dela Cruz',
                'contact_number' => '09171234567',
                'email' => 'juan.delacruz@email.com',
                'address' => 'Block 5, Lot 12, Poblacion I',
                'concern_type' => 'Road Clearing / Obstruction',
                'description' => 'There is a large truck blocking the main road on Main Street. It has been parked there for 3 days causing traffic congestion.',
                'latitude' => 14.2800,
                'longitude' => 121.4150,
                'gps_accuracy' => 5.2,
                'urgency_level' => 'High',
                'status' => 'Submitted',
                'created_at' => Carbon::now()->subDays(2),
            ],
            [
                'full_name' => 'Maria Santos',
                'contact_number' => '09187654321',
                'email' => 'maria.santos@email.com',
                'address' => 'Purok 3, Poblacion II',
                'concern_type' => 'Infrastructure Concern',
                'description' => 'The drainage system near the market is clogged causing flooding every time it rains. Water reaches knee-high level.',
                'latitude' => 14.2850,
                'longitude' => 121.4180,
                'gps_accuracy' => 8.5,
                'urgency_level' => 'Critical',
                'status' => 'Under Review',
                'created_at' => Carbon::now()->subDays(5),
            ],
            [
                'full_name' => 'Pedro Reyes',
                'contact_number' => '09171112222',
                'email' => null,
                'address' => 'Sitio Maligaya, Bagumbayan',
                'concern_type' => 'Environmental Concern',
                'description' => 'Garbage has been piling up at the corner of our street for 2 weeks. It is starting to smell and attracting rats.',
                'latitude' => 14.2750,
                'longitude' => 121.4100,
                'gps_accuracy' => 12.0,
                'urgency_level' => 'Medium',
                'status' => 'Assigned',
                'assigned_personnel' => 'Brgy. Captain Gomez',
                'created_at' => Carbon::now()->subDays(8),
            ],
            [
                'full_name' => 'Ana Cruz',
                'contact_number' => '09189998888',
                'email' => 'ana.cruz@email.com',
                'address' => 'Zone I, near Elementary School',
                'concern_type' => 'Public Service Concern',
                'description' => 'Streetlight has been broken for a month. The area is very dark at night and unsafe for residents especially women.',
                'latitude' => 14.2950,
                'longitude' => 121.4200,
                'gps_accuracy' => 6.8,
                'urgency_level' => 'High',
                'status' => 'In Progress',
                'assigned_personnel' => 'Engr. Ramirez',
                'remarks' => 'Parts have been ordered. Installation scheduled next week.',
                'created_at' => Carbon::now()->subDays(15),
            ],
            [
                'full_name' => 'Roberto Garcia',
                'contact_number' => '09177773333',
                'email' => 'roberto.garcia@email.com',
                'address' => 'Barangay Zone II',
                'concern_type' => 'Disaster / Risk Concern',
                'description' => 'There is a large crack on the road near the bridge. It looks dangerous and might collapse during heavy rain.',
                'latitude' => 14.3000,
                'longitude' => 121.4230,
                'gps_accuracy' => 4.5,
                'urgency_level' => 'Critical',
                'status' => 'Assigned',
                'assigned_personnel' => 'MDRRMO Team',
                'created_at' => Carbon::now()->subHours(12),
            ],
            [
                'full_name' => 'Lisa Fernandez',
                'contact_number' => '09156667777',
                'email' => 'lisa.fernandez@email.com',
                'address' => 'Duhat, near Chapel',
                'concern_type' => 'Complaint',
                'description' => 'Noisy karaoke from neighbor every night until 2 AM. We have asked them politely to lower the volume but they refuse.',
                'latitude' => 14.2650,
                'longitude' => 121.4050,
                'gps_accuracy' => 15.2,
                'urgency_level' => 'Low',
                'status' => 'Resolved',
                'assigned_personnel' => 'Tanod Jose',
                'remarks' => 'Mediation conducted. Both parties reached agreement.',
                'created_at' => Carbon::now()->subDays(20),
            ],
            [
                'full_name' => 'Carlos Ramos',
                'contact_number' => '09182223333',
                'email' => null,
                'address' => 'Jasaan, Main Road',
                'concern_type' => 'Infrastructure Concern',
                'description' => 'Pothole on the main road is getting bigger. Several motorcycles have had accidents because of it.',
                'latitude' => 14.2700,
                'longitude' => 121.4130,
                'gps_accuracy' => 7.3,
                'urgency_level' => 'High',
                'status' => 'Submitted',
                'created_at' => Carbon::now()->subDays(1),
            ],
            [
                'full_name' => 'Elena Martinez',
                'contact_number' => '09195554444',
                'email' => 'elena.martinez@email.com',
                'address' => 'Malinao, near Church',
                'concern_type' => 'Request',
                'description' => 'Requesting assistance for senior citizen ID renewal. Office is closed when we visit.',
                'latitude' => 14.3200,
                'longitude' => 121.4310,
                'gps_accuracy' => 9.8,
                'urgency_level' => 'Low',
                'status' => 'Under Review',
                'created_at' => Carbon::now()->subDays(3),
            ],
            [
                'full_name' => 'Miguel Torres',
                'contact_number' => '09171119999',
                'email' => 'miguel.torres@email.com',
                'address' => 'Palasan, Riverside',
                'concern_type' => 'Environmental Concern',
                'description' => 'Illegal dumping happening along the river. People are throwing construction waste and old appliances.',
                'latitude' => 14.2600,
                'longitude' => 121.4070,
                'gps_accuracy' => 11.5,
                'urgency_level' => 'Medium',
                'status' => 'Assigned',
                'assigned_personnel' => 'Environmental Officer',
                'created_at' => Carbon::now()->subDays(10),
            ],
            [
                'full_name' => 'Sofia Aquino',
                'contact_number' => '09188887777',
                'email' => 'sofia.aquino@email.com',
                'address' => 'San Jose, Purok 5',
                'concern_type' => 'Public Service Concern',
                'description' => 'Water supply has been interrupted for 3 days. We are having difficulty with daily needs.',
                'latitude' => 14.2400,
                'longitude' => 121.3930,
                'gps_accuracy' => 6.2,
                'urgency_level' => 'Critical',
                'status' => 'In Progress',
                'assigned_personnel' => 'Water District Office',
                'remarks' => 'Main pipe repair ongoing. Expected restoration tomorrow.',
                'created_at' => Carbon::now()->subDays(4),
            ],
            [
                'full_name' => 'Ramon Cruz',
                'contact_number' => '09172221111',
                'email' => null,
                'address' => 'San Pablo Norte',
                'concern_type' => 'Road Clearing / Obstruction',
                'description' => 'Street vendors are blocking the sidewalk. Pedestrians are forced to walk on the road.',
                'latitude' => 14.2350,
                'longitude' => 121.3900,
                'gps_accuracy' => 13.7,
                'urgency_level' => 'Medium',
                'status' => 'Submitted',
                'created_at' => Carbon::now()->subHours(18),
            ],
            [
                'full_name' => 'Linda Bautista',
                'contact_number' => '09193334444',
                'email' => 'linda.bautista@email.com',
                'address' => 'Santisima Cruz, near School',
                'concern_type' => 'Governance Concern',
                'description' => 'Requesting copy of barangay clearance. Online portal is not working.',
                'latitude' => 14.2800,
                'longitude' => 121.4250,
                'gps_accuracy' => 5.9,
                'urgency_level' => 'Low',
                'status' => 'Resolved',
                'assigned_personnel' => 'Brgy. Secretary',
                'remarks' => 'Clearance issued. Portal issue reported to IT.',
                'created_at' => Carbon::now()->subDays(7),
            ],
        ];

        foreach ($concerns as $concernData) {
            // Auto-detect barangay from GPS
            if (isset($concernData['latitude']) && isset($concernData['longitude'])) {
                $barangayData = BarangayAssignmentService::detectBarangay(
                    $concernData['latitude'],
                    $concernData['longitude']
                );
                
                $concernData['detected_barangay'] = $barangayData['detected_barangay'];
                $concernData['assigned_barangay_office'] = $barangayData['assigned_barangay_office'];
                $concernData['location_name'] = $barangayData['location_name'];
            }

            // Generate record ID
            $concernData['record_id'] = Record::generateRecordId();
            $concernData['date_submitted'] = $concernData['created_at'];
            
            // Set assigned office from barangay office
            if (isset($concernData['assigned_barangay_office'])) {
                $concernData['assigned_office'] = $concernData['assigned_barangay_office'];
            }

            Record::create($concernData);
        }

        $this->command->info('Phase 2 concern data with GPS coordinates seeded successfully!');
    }
}
