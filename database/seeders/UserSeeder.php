<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing users
        User::truncate();

        // ==========================================
        // CREATE DILG ADMIN ACCOUNT
        // ==========================================
        
        User::create([
            'name' => 'DILG Administrator',
            'email' => 'admin@dilg.gov.ph',
            'password' => Hash::make('password'),
            'role' => 'dilg_admin',
            'assigned_barangay' => null,
        ]);

        // ==========================================
        // CREATE BARANGAY STAFF ACCOUNTS
        // ==========================================
        
        // Load barangays from config
        $barangays = config('santa_cruz_barangays.barangays', []);

        foreach ($barangays as $barangayData) {
            $barangayName = $barangayData['name'];
            
            // Generate email slug from barangay name
            $emailSlug = strtolower($barangayName);
            $emailSlug = str_replace(' ', '-', $emailSlug);
            $emailSlug = str_replace('(', '', $emailSlug);
            $emailSlug = str_replace(')', '', $emailSlug);
            $emailSlug = str_replace('.', '', $emailSlug);
            
            $email = $emailSlug . '@barangay.dilg.gov.ph';
            
            User::create([
                'name' => 'Barangay Staff - ' . $barangayName,
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => 'barangay_staff',
                'assigned_barangay' => $barangayName,
            ]);
        }

        $this->command->info('✅ Created 1 DILG Admin account');
        $this->command->info('✅ Created ' . count($barangays) . ' Barangay Staff accounts (Complete Santa Cruz, Laguna)');
        $this->command->info('📧 All accounts use password: password');
    }
}
