<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seedPassword = (string) config('auth.seed_default_password');

        if (strlen($seedPassword) < 12) {
            throw new \RuntimeException(
                'SEED_DEFAULT_PASSWORD must be set to a value of at least 12 characters before running UserSeeder.'
            );
        }

        $passwordHash = Hash::make($seedPassword);

        // Clear existing users.
        User::truncate();

        User::create([
            'name' => 'DILG Administrator',
            'email' => 'admin@dilg.gov.ph',
            'password' => $passwordHash,
            'role' => 'dilg_admin',
            'assigned_barangay' => null,
        ]);

        $barangays = config('santa_cruz_barangays.barangays', []);

        foreach ($barangays as $barangayData) {
            $barangayName = $barangayData['name'];

            $emailSlug = strtolower($barangayName);
            $emailSlug = str_replace(' ', '-', $emailSlug);
            $emailSlug = str_replace(['(', ')', '.'], '', $emailSlug);

            User::create([
                'name' => 'Barangay Staff - '.$barangayName,
                'email' => $emailSlug.'@barangay.dilg.gov.ph',
                'password' => $passwordHash,
                'role' => 'barangay_staff',
                'assigned_barangay' => $barangayName,
            ]);
        }

        $this->command->info('Created 1 DILG Admin account.');
        $this->command->info('Created '.count($barangays).' Barangay Staff accounts.');
        $this->command->warn('Assign unique passwords to every deployed account immediately after seeding.');
    }
}
