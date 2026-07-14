<?php

namespace App\Services;

class BarangayAssignmentService
{
    public static function validateMunicipalityCoverage(float $latitude, float $longitude): array
    {
        $geoJson = self::loadGeoJson(public_path('gis/boundary.geojson'));
        $isInside = $geoJson !== null && self::pointIntersectsFeatureCollection($latitude, $longitude, $geoJson);

        return [
            'is_inside_santa_cruz' => $isInside,
            'municipality_name' => $isInside ? 'Santa Cruz' : null,
            'municipality_context' => $isInside ? 'Inside Santa Cruz Municipality' : 'Outside Santa Cruz Coverage',
        ];
    }

    public static function detectBarangay(float $latitude, float $longitude): array
    {
        $path = public_path('gis/santa_cruz_barangays.geojson');
        $geoJson = self::loadGeoJson($path);

        if ($geoJson === null || empty($geoJson['features'])) {
            return [
                'detected_barangay' => null,
                'barangay_detection_method' => 'barangay_polygon',
                'barangay_detection_status' => 'barangay_boundary_unavailable',
                'needs_manual_barangay_review' => true,
            ];
        }

        foreach ($geoJson['features'] as $feature) {
            $name = self::extractBarangayName($feature);
            $geometry = $feature['geometry'] ?? null;

            if ($name && $geometry && self::isPointInGeometry($latitude, $longitude, $geometry)) {
                return [
                    'detected_barangay' => $name,
                    'barangay_detection_method' => 'barangay_polygon',
                    'barangay_detection_status' => 'auto_detected',
                    'needs_manual_barangay_review' => false,
                ];
            }
        }

        return [
            'detected_barangay' => null,
            'barangay_detection_method' => 'barangay_polygon',
            'barangay_detection_status' => 'barangay_not_matched',
            'needs_manual_barangay_review' => true,
        ];
    }

    public static function assignReportLocation(float $latitude, float $longitude): array
    {
        $municipality = self::validateMunicipalityCoverage($latitude, $longitude);

        if (! $municipality['is_inside_santa_cruz']) {
            return array_merge($municipality, [
                'municipality_validated' => false,
                'detected_barangay' => null,
                'barangay_detection_method' => 'not_attempted',
                'barangay_detection_status' => 'outside_coverage',
                'needs_manual_barangay_review' => true,
                'assigned_barangay_office' => null,
                'location_context' => 'Outside Santa Cruz Coverage',
            ]);
        }

        $barangay = self::detectBarangay($latitude, $longitude);

        return array_merge($municipality, $barangay, [
            'municipality_validated' => true,
            'assigned_barangay_office' => $barangay['detected_barangay']
                ? 'Barangay Hall - '.$barangay['detected_barangay']
                : null,
            'location_context' => $barangay['detected_barangay']
                ? 'Inside Barangay Boundary'
                : 'Inside Santa Cruz; Needs Barangay Review',
        ]);
    }

    /** @deprecated Use assignReportLocation(). */
    public static function assignBarangay($latitude, $longitude): array
    {
        if ($latitude === null || $longitude === null) {
            return [
                'is_inside_santa_cruz' => false,
                'municipality_validated' => false,
                'municipality_name' => null,
                'municipality_context' => 'GPS Missing',
                'detected_barangay' => null,
                'barangay_detection_method' => 'not_attempted',
                'barangay_detection_status' => 'gps_missing',
                'needs_manual_barangay_review' => true,
                'assigned_barangay_office' => null,
                'location_context' => 'GPS Missing',
            ];
        }

        return self::assignReportLocation((float) $latitude, (float) $longitude);
    }

    public static function getAllBarangays(): array
    {
        return array_column(config('santa_cruz_barangays.barangays', []), 'name');
    }

    public static function getBarangayByName(string $barangayName): ?array
    {
        foreach (config('santa_cruz_barangays.barangays', []) as $barangay) {
            if (strcasecmp($barangay['name'], $barangayName) === 0) {
                return $barangay;
            }
        }

        return null;
    }

    public static function getViolationTypes(): array
    {
        return config('santa_cruz_barangays.violation_types', []);
    }

    public static function getStatuses(): array
    {
        return config('santa_cruz_barangays.statuses', []);
    }

    public static function getVerificationStatuses(): array
    {
        return config('santa_cruz_barangays.verification_statuses', []);
    }

    private static function loadGeoJson(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) && ($data['type'] ?? null) === 'FeatureCollection' ? $data : null;
    }

    private static function pointIntersectsFeatureCollection(float $latitude, float $longitude, array $geoJson): bool
    {
        foreach ($geoJson['features'] ?? [] as $feature) {
            if (isset($feature['geometry']) && self::isPointInGeometry($latitude, $longitude, $feature['geometry'])) {
                return true;
            }
        }

        return false;
    }

    private static function extractBarangayName(array $feature): ?string
    {
        $properties = $feature['properties'] ?? [];

        foreach (['barangay', 'name', 'ADM4_EN'] as $key) {
            if (! empty($properties[$key])) {
                return trim((string) $properties[$key]);
            }
        }

        return null;
    }

    private static function isPointInGeometry(float $latitude, float $longitude, array $geometry): bool
    {
        $coordinates = $geometry['coordinates'] ?? [];

        return match ($geometry['type'] ?? null) {
            'Polygon' => self::isPointInPolygon($latitude, $longitude, $coordinates),
            'MultiPolygon' => collect($coordinates)->contains(
                fn (array $polygon) => self::isPointInPolygon($latitude, $longitude, $polygon)
            ),
            default => false,
        };
    }

    private static function isPointInPolygon(float $latitude, float $longitude, array $polygon): bool
    {
        $outerRing = $polygon[0] ?? [];

        if (! self::isPointInRing($latitude, $longitude, $outerRing)) {
            return false;
        }

        // A point inside an interior ring is inside a polygon hole, not the polygon.
        foreach (array_slice($polygon, 1) as $hole) {
            if (self::isPointInRing($latitude, $longitude, $hole)) {
                return false;
            }
        }

        return true;
    }

    private static function isPointInRing(float $latitude, float $longitude, array $ring): bool
    {
        $inside = false;
        $count = count($ring);

        if ($count < 3) {
            return false;
        }

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            [$xi, $yi] = $ring[$i];
            [$xj, $yj] = $ring[$j];
            $crossesLatitude = ($yi > $latitude) !== ($yj > $latitude);

            if ($crossesLatitude) {
                $intersectionLongitude = ($xj - $xi) * ($latitude - $yi) / ($yj - $yi) + $xi;
                if ($longitude < $intersectionLongitude) {
                    $inside = ! $inside;
                }
            }
        }

        return $inside;
    }
}
