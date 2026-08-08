<?php

namespace App\Support;

final class CitizenViolationType
{
    /**
     * Internal storage value for a report that has no citizen classification.
     *
     * This is not a citizen-selectable violation type and must not be exposed as
     * a citizen choice, AI result, or official staff classification.
     */
    public const UNCLASSIFIED = 'Unclassified';

    public const STAFF_LABEL = 'Awaiting Staff Classification';

    public static function forStorage(?string $citizenSelection): string
    {
        return $citizenSelection === null
            ? self::UNCLASSIFIED
            : $citizenSelection;
    }

    public static function citizenSelection(?string $storedValue): ?string
    {
        return self::isUnclassified($storedValue)
            ? null
            : $storedValue;
    }

    public static function hasCitizenClassification(?string $storedValue): bool
    {
        return self::citizenSelection($storedValue) !== null;
    }

    public static function staffLabel(?string $storedValue): string
    {
        return self::isUnclassified($storedValue)
            ? self::STAFF_LABEL
            : (string) $storedValue;
    }

    public static function isUnclassified(?string $storedValue): bool
    {
        return $storedValue === null || $storedValue === self::UNCLASSIFIED;
    }
}
