<?php

namespace Truschery\Idem\Tests;

//use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as Orchestra;
use Truschery\Idem\Middleware\Idempotent;
use Truschery\Idem\Providers\IdempotencyServiceProvider;
use function Illuminate\Support\microseconds;

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
            Route::post('/idempotent', function (){
                return response()->json([
                    'timestamp' => microtime(true)
                ]);
            });
        });
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app->singleton('cache', function ($app) {
            return new \Illuminate\Cache\Repository(
                new \Illuminate\Cache\ArrayStore
            );
        });
    }
    
}
