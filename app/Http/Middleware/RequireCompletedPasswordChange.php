<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireCompletedPasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->must_change_password) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'A password change is required before continuing.',
            ], 423);
        }

        return redirect()->guest(route('password.edit'))->with(
            'info',
            'Create a private new password before accessing CIVICLEAR.'
        );
    }
}
