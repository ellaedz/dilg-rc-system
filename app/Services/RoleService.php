<?php

namespace App\Services;

use App\Models\User;

class RoleService
{
    /**
     * Check if user is DILG Admin
     */
    public static function isDilgAdmin(?User $user): bool
    {
        return $user && $user->role === 'dilg_admin';
    }

    /**
     * Check if user is Barangay Staff
     */
    public static function isBarangayStaff(?User $user): bool
    {
        return $user && $user->role === 'barangay_staff';
    }

    /**
     * Get assigned barangay for user
     */
    public static function getAssignedBarangay(?User $user): ?string
    {
        return $user ? $user->assigned_barangay : null;
    }

    /**
     * Check if user can access a specific barangay
     * 
     * Rules:
     * - DILG Admin can access all barangays
     * - Barangay Staff can only access their assigned barangay
     * 
     * Note: Comparison is case-insensitive to handle URL variations
     */
    public static function canAccessBarangay(?User $user, string $barangay): bool
    {
        if (!$user) {
            return false;
        }

        // DILG Admin can access all barangays
        if (self::isDilgAdmin($user)) {
            return true;
        }

        // Barangay Staff can only access their assigned barangay (case-insensitive)
        if (self::isBarangayStaff($user)) {
            return strcasecmp($user->assigned_barangay, $barangay) === 0;
        }

        return false;
    }
}
