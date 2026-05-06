<?php

namespace Truschery\Idem\Tests;

//use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Orchestra\Testbench\TestCase as Orchestra;
use Truschery\Idem\Providers\IdempotencyServiceProvider;

abstract class TestCase extends Orchestra
{

    protected function getPackageProviders($app): array
    {
        return [
          IdempotencyServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app->singleton('cache', function ($app) {
            return new \Illuminate\Cache\Repository(
                new \Illuminate\Cache\ArrayStore
            );
        });
    }
    
}
