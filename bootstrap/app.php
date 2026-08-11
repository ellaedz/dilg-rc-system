<?php

use App\Http\Middleware\BarangayStaffMiddleware;
use App\Http\Middleware\DilgAdminMiddleware;
use App\Http\Middleware\VerifyCloudTaskOidc;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'dilg.admin' => DilgAdminMiddleware::class,
            'barangay.staff' => BarangayStaffMiddleware::class,
            'cloud.tasks.oidc' => VerifyCloudTaskOidc::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (
            PostTooLargeException $exception,
            Request $request
        ) {
            if (! $request->is('api/mobile/reports')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'The request body exceeds the server upload limit.',
                'error' => ['code' => 'REQUEST_BODY_TOO_LARGE'],
            ], 413);
        });
    })->create();
