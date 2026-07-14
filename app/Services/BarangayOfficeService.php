<?php

namespace App\Services;

class BarangayOfficeService
{
    public function calculateDistanceKm(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $earthRadiusKm = 6371.0088;
        $latitudeDelta = deg2rad($lat2 - $lat1);
        $longitudeDelta = deg2rad($lng2 - $lng1);

        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($longitudeDelta / 2) ** 2;

        return round($earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a)), 3);
    }

    public function findNearestValidatedOffice(float $latitude, float $longitude): ?array
    {
        $offices = collect(config('santa_cruz_barangay_halls', []))
            ->filter(fn (array $office) => $this->hasUsableCoordinates($office));

        if ($offices->isEmpty()) {
            return null;
        }

        $nearest = $offices
            ->map(function (array $office) use ($latitude, $longitude) {
                $office['distance_km'] = $this->calculateDistanceKm(
                    $latitude,
                    $longitude,
                    (float) $office['latitude'],
                    (float) $office['longitude']
                );

                return $office;
            })
            ->sortBy('distance_km')
            ->first();

        $validated = strcasecmp($nearest['validation_status'] ?? '', 'Verified') === 0;
        $nearest['recommendation_status'] = $validated ? 'validated' : 'provisional';
        $nearest['recommendation_notice'] = $validated
            ? 'Recommended Barangay Office for Follow-up.'
            : 'Office location requires validation before production deployment.';

        return $nearest;
    }

    private function hasUsableCoordinates(array $office): bool
    {
        return isset($office['latitude'], $office['longitude'])
            && is_numeric($office['latitude'])
            && is_numeric($office['longitude'])
            && (float) $office['latitude'] >= -90
            && (float) $office['latitude'] <= 90
            && (float) $office['longitude'] >= -180
            && (float) $office['longitude'] <= 180
            && ! ((float) $office['latitude'] === 0.0 && (float) $office['longitude'] === 0.0);
    }
}
