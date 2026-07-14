<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\RoleService;

class DilgAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Only allow authenticated users with role 'dilg_admin'
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Check if user is authenticated and is DILG Admin
        if (!$user || !RoleService::isDilgAdmin($user)) {
            abort(403, 'Access denied. DILG Admin role required.');
        }

        return $next($request);
    }
}
