<?php

/**
 * CIVICLEAR final Santa Cruz barangay-hall coordinates.
 *
 * These 26 points were reconciled against the authoritative MPDO barangay
 * polygons and independently cross-checked against the supplied Excel master
 * and JSON code dataset on September 9, 2026.
 */

$halls = [
    ['043426001', 'Alipit', 14.2238875, 121.405203125, 'Final reconciled hall point', 'Final location used by CIVICLEAR.'],
    ['043426002', 'Bagumbayan', 14.26792, 121.39935, 'OpenStreetMap mapped barangay hall', 'Replaces earlier manual point.'],
    ['043426003', 'Bubukal', 14.25851, 121.39832, 'OpenStreetMap mapped barangay hall', 'Matches earlier manual research closely.'],
    ['043426004', 'Calios', 14.2705, 121.4051, 'Researcher-verified map point', 'Final reconciled point.'],
    ['043426005', 'Duhat', 14.25349, 121.3827, 'Researcher-verified map point', 'Final reconciled point.'],
    ['043426006', 'Gatid', 14.260583, 121.383434, 'Researcher-verified map point', 'Final reconciled point.'],
    ['043426007', 'Jasaan', 14.22416, 121.3945, 'Researcher-verified map point', 'Final reconciled point.'],
    ['043426008', 'Labuin', 14.2501, 121.40083, 'OpenStreetMap mapped barangay hall', 'Corrects earlier manual point that fell in Bubukal.'],
    ['043426009', 'Malinao', 14.2356, 121.3916, 'Researcher-verified map point', 'Final reconciled point.'],
    ['043426010', 'Oogong', 14.22638, 121.40037, 'OpenStreetMap mapped barangay hall', 'Matches earlier manual research closely.'],
    ['043426011', 'Pagsawitan', 14.26569, 121.42659, 'OpenStreetMap mapped barangay hall', 'Matches earlier manual research closely.'],
    ['043426012', 'Palasan', 14.25748, 121.41899, 'OpenStreetMap mapped barangay hall', 'Higher-precision point used to avoid boundary rounding issue.'],
    ['043426013', 'Patimbao', 14.27028, 121.41838, 'OpenStreetMap mapped barangay hall', 'Final mapped government compound location.'],
    ['043426014', 'Poblacion I', 14.2755, 121.417, 'Researcher-verified map point', 'Final reconciled point.'],
    ['043426015', 'Poblacion II', 14.27947, 121.41704, 'OpenStreetMap mapped barangay hall', 'Matches earlier manual research closely.'],
    ['043426016', 'Poblacion III', 14.28254, 121.4162, 'Researcher-verified map point', 'Final reconciled point.'],
    ['043426017', 'Poblacion IV', 14.28479, 121.4156, 'Researcher-verified map point', 'Final reconciled point.'],
    ['043426018', 'Poblacion V', 14.28534, 121.41224, 'OpenStreetMap mapped barangay hall', 'Matches earlier manual research closely.'],
    ['043426019', 'San Jose', 14.23744, 121.4039, 'Researcher-verified map point', 'Final reconciled point.'],
    ['043426020', 'San Juan', 14.2442, 121.4071, 'Researcher-verified map point', 'Final reconciled point.'],
    ['043426021', 'San Pablo Norte', 14.29135, 121.4132, 'Researcher-verified map point', 'Final reconciled point.'],
    ['043426022', 'San Pablo Sur', 14.2829125, 121.416953125, 'Current mapped hall listing / Plus Code', 'Resolved and inside San Pablo Sur.'],
    ['043426023', 'Santisima Cruz', 14.28783, 121.41115, 'OpenStreetMap mapped barangay hall', 'Final reconciled point.'],
    ['043426024', 'Santo Angel Central', 14.2855375, 121.408015625, 'Current mapped hall listing / Plus Code', 'Resolved and inside Santo Angel Central.'],
    ['043426025', 'Santo Angel Norte', 14.28756, 121.40742, 'OpenStreetMap mapped barangay hall', 'Final reconciled point.'],
    ['043426026', 'Santo Angel Sur', 14.281638, 121.413547, 'Current mapped hall listing / Plus Code', 'Current Zamora St hall location; replaces old hall point.'],
];

return array_map(
    static fn (array $hall): array => [
        'psgc' => $hall[0],
        'barangay' => $hall[1],
        'office_name' => 'Barangay Hall - '.$hall[1],
        'latitude' => $hall[2],
        'longitude' => $hall[3],
        'address' => $hall[1].', Santa Cruz, Laguna',
        'osm_type' => null,
        'osm_id' => null,
        'source' => $hall[4],
        'validation_status' => 'Verified',
        'boundary_validation' => 'MPDO polygon verified',
        'verification_note' => $hall[5],
    ],
    $halls,
);
