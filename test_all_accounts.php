<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== Testing All User Accounts ===\n\n";

$testPassword = 'password';

// Test DILG Admin
$admin = User::where('email', 'admin@dilg.gov.ph')->first();
if ($admin) {
    $passwordWorks = Hash::check($testPassword, $admin->password);
    echo "✅ DILG Admin: " . ($passwordWorks ? "Password OK" : "❌ Password FAILED") . "\n";
} else {
    echo "❌ DILG Admin account NOT FOUND\n";
}

echo "\n=== Barangay Accounts ===\n";

// Get all barangay users
$barangays = User::where('role', 'barangay_staff')->orderBy('assigned_barangay')->get();

$workingCount = 0;
$failedCount = 0;

foreach ($barangays as $user) {
    $passwordWorks = Hash::check($testPassword, $user->password);
    
    if ($passwordWorks) {
        echo "✅ {$user->assigned_barangay} ({$user->email})\n";
        $workingCount++;
    } else {
        echo "❌ {$user->assigned_barangay} ({$user->email}) - PASSWORD FAILED\n";
        $failedCount++;
    }
}

echo "\n=== Summary ===\n";
echo "Total Barangay Accounts: " . $barangays->count() . "\n";
echo "Working: $workingCount\n";
echo "Failed: $failedCount\n";

if ($failedCount == 0 && $workingCount == 26) {
    echo "\n🎉 ALL ACCOUNTS ARE WORKING!\n";
} else {
    echo "\n⚠️ Some accounts have issues!\n";
}
