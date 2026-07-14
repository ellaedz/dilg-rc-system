<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$halls = require $root.'/config/santa_cruz_barangay_halls.php';

$features = array_map(static function (array $hall): array {
    return [
        'type' => 'Feature',
        'geometry' => [
            'type' => 'Point',
            'coordinates' => [(float) $hall['longitude'], (float) $hall['latitude']],
        ],
        'properties' => [
            'barangay' => $hall['barangay'],
            'office_name' => $hall['office_name'],
            'address' => $hall['address'],
            'source' => $hall['source'] ?? 'unknown',
            'validation_status' => $hall['validation_status'] ?? 'unknown',
            'verification_note' => $hall['verification_note'] ?? null,
            'osm_type' => $hall['osm_type'] ?? null,
            'osm_id' => $hall['osm_id'] ?? null,
        ],
    ];
}, $halls);

$geoJson = [
    'type' => 'FeatureCollection',
    'name' => 'Santa Cruz Barangay Halls',
    'crs' => [
        'type' => 'name',
        'properties' => ['name' => 'urn:ogc:def:crs:OGC:1.3:CRS84'],
    ],
    'features' => $features,
];

$written = file_put_contents(
    $root.'/public/gis/barangay_halls.geojson',
    json_encode($geoJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL
);

if ($written === false) {
    fwrite(STDERR, "Unable to write barangay_halls.geojson.\n");
    exit(1);
}

printf("Synced %d barangay hall features.\n", count($features));
