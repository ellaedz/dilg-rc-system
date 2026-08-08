<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectOversizedPostBody
{
    public function handle(Request $request, Closure $next): Response
    {
        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
        $postMaxBytes = $this->iniBytes((string) ini_get('post_max_size'));

        if ($postMaxBytes > 0 && $contentLength > $postMaxBytes) {
            return new JsonResponse([
                'success' => false,
                'message' => 'The request body exceeds the server upload limit.',
                'error' => ['code' => 'REQUEST_BODY_TOO_LARGE'],
            ], 413);
        }

        return $next($request);
    }

    private function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
