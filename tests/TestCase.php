<?php

namespace Truschery\Idem\Tests;

// use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as Orchestra;
use Truschery\Idem\Middleware\Idempotent;
use Truschery\Idem\Providers\IdempotencyServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            IdempotencyServiceProvider::class,
        ];
    }

    protected function defineRoutes($router): void
    {

        $router->middleware(Idempotent::class)->group(function () {
            Route::post('/idempotent', function () {
                return response()->json([
                    'timestamp' => microtime(true),
                ]);
            });

            Route::post('/idempotent-500', function () {
                return throw new \Exception('Server error', 500);
            });
        });
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app->singleton('cache', function ($app) {
            return new Repository(
                new ArrayStore
            );
        });
    }

    protected function defineEnvironment($app)
    {
        $app->config->set('database.default', 'testing');

        $app->config->set('database.connections.testing', [
            'driver' => 'sqlite',
            'prefix' => '',
            'database' => ':memory:',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
