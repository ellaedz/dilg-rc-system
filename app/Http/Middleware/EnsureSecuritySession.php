<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSecuritySession
{
    public const SESSION_VERSION_KEY = 'security_session_version';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $expectedVersion = (int) $user->session_version;
        $sessionVersion = $request->session()->get(self::SESSION_VERSION_KEY);

        if ($sessionVersion === null && $expectedVersion === 1 && $user->is_active) {
            $request->session()->put(self::SESSION_VERSION_KEY, $expectedVersion);

            return $next($request);
        }

        if (! $user->is_active || (int) $sessionVersion !== $expectedVersion) {
            Auth::guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session is no longer valid. Please sign in again.',
                ], 401);
            }

            return redirect()->route('login')->with(
                'error',
                'Your session is no longer valid. Please sign in again.'
            );
        }

        return $next($request);
    }
}
