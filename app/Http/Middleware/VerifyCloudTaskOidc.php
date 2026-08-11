<?php

namespace App\Http\Middleware;

use App\Contracts\VerifiesCloudTaskOidc;
use App\Exceptions\CloudTaskIdentityException;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCloudTaskOidc
{
    public function __construct(private readonly VerifiesCloudTaskOidc $verifier) {}

    public function handle(Request $request, Closure $next): Response
    {
        $authorization = $request->header('Authorization');
        if (! is_string($authorization)
            || ! preg_match('/\ABearer ([A-Za-z0-9._~-]{20,8192})\z/D', $authorization, $matches)) {
            return $this->denied(401);
        }

        try {
            $claims = $this->verifier->verify($matches[1]);
        } catch (CloudTaskIdentityException) {
            return $this->denied(403);
        }

        $request->attributes->set('verified_cloud_task_identity', $claims);

        return $next($request);
    }

    private function denied(int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'The task request is not authorized.',
        ], $status);
    }
}
