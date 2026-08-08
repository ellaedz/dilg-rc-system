<?php

namespace Tests;

use App\Services\Phase9APostgresSafetyGuard;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $application = parent::createApplication();
        $connectionName = (string) config('database.default');
        $driver = config("database.connections.{$connectionName}.driver");

        if ($driver !== 'pgsql') {
            return $application;
        }

        if ($connectionName !== (string) config('phase9a.connection')) {
            throw new RuntimeException(
                'PostgreSQL PHPUnit runs require the dedicated Phase 9A test connection.'
            );
        }

        $schema = (string) config("database.connections.{$connectionName}.search_path");
        $expectedDatabase = (string) env('PHASE9A_EXPECTED_DATABASE');

        try {
            $application->make(Phase9APostgresSafetyGuard::class)
                ->assertDisposableConnection($connectionName, $schema, $expectedDatabase);
        } finally {
            // The safety check must not consume one Session Pooler slot for
            // every test application. Database-using tests reconnect after
            // the guard and are disconnected during application teardown.
            $application->make('db')->disconnect($connectionName);
        }

        return $application;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestsDuringMaintenance::class);

        $connectionName = (string) config('database.default');

        if (config("database.connections.{$connectionName}.driver") !== 'pgsql') {
            return;
        }

        $database = $this->app->make('db');

        $this->beforeApplicationDestroyed(
            static fn () => $database->disconnect($connectionName),
        );
    }
}
