<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\RoleService;

class BarangayStaffMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Allow DILG Admin (monitoring) or Barangay Staff (assigned barangay only)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Check if user is authenticated
        if (!$user) {
            abort(403, 'Access denied. Authentication required.');
        }

        // DILG Admin can access any barangay (for monitoring purposes)
        if (RoleService::isDilgAdmin($user)) {
            return $next($request);
        }

        // Barangay Staff can only access their assigned barangay
        if (RoleService::isBarangayStaff($user)) {
            $requestedBarangay = $request->route('barangay');

            // Check if barangay staff is accessing their assigned barangay
            if ($requestedBarangay && !RoleService::canAccessBarangay($user, $requestedBarangay)) {
                abort(403, 'Access denied. You can only access reports for ' . $user->assigned_barangay . '.');
            }

            return $next($request);
        }

        // User has neither role
        abort(403, 'Access denied. Invalid user role.');
    }
}
