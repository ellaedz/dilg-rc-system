<?php

namespace App\Support;

final class OfficialViolationType
{
    private const AI_TO_OFFICIAL = [
        'construction_materials' => 'Construction Materials Obstruction',
        'garbage_debris' => 'Waste/Garbage Obstruction',
        'illegal_parking' => 'Illegal Parking',
        'road_obstruction' => 'Road Obstruction',
        'sidewalk_obstruction' => 'Sidewalk Obstruction',
        'vending_obstruction' => 'Vending Obstruction',
        'encroachment' => 'Encroachment',
        'abandoned_vehicle' => 'Abandoned Vehicle',
        'illegal_structure' => 'Illegal Structure',
        'other_road_clearing_violation' => 'Other Road Clearing Violation',
    ];

    public static function all(): array
    {
        return config('santa_cruz_barangays.violation_types', []);
    }

    public static function fromAi(?string $category): ?string
    {
        if (! $category || $category === 'no_violation') {
            return null;
        }

        if (isset(self::AI_TO_OFFICIAL[$category])) {
            return self::AI_TO_OFFICIAL[$category];
        }

        foreach (self::all() as $officialType) {
            if (strcasecmp($officialType, str_replace('_', ' ', $category)) === 0) {
                return $officialType;
            }
        }

        return null;
    }

    public static function label(?string $category, string $fallback = 'Not available'): string
    {
        if (! $category) {
            return $fallback;
        }

        if ($category === 'no_violation') {
            return 'No Clear Violation Detected';
        }

        return self::fromAi($category) ?? ucwords(str_replace('_', ' ', $category));
    }
}
