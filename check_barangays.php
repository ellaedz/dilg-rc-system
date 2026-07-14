<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ViolationReport;

echo "=== Barangays in Violation Reports ===\n\n";

$barangays = ViolationReport::select('detected_barangay')
    ->distinct()
    ->orderBy('detected_barangay')
    ->get()
    ->pluck('detected_barangay');

foreach ($barangays as $barangay) {
    $count = ViolationReport::where('detected_barangay', $barangay)->count();
    echo "✅ {$barangay} ({$count} reports)\n";
}

echo "\n=== Total: " . $barangays->count() . " barangays with reports ===\n";
