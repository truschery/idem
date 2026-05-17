<?php

namespace Truschery\Idem\Providers;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\ServiceProvider;
use Truschery\Idem\Config\IdempotencyConfig;
use Truschery\Idem\Contracts\CacheableSpecification;
use Truschery\Idem\Contracts\IdempotencyStore;
use Truschery\Idem\IdempotencyManager;
use Truschery\Idem\Method;
use Truschery\Idem\Specs\AlwaysCacheableSpecification;
use Truschery\Idem\Stores\CacheStore;

class IdempotencyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CacheRepository::class, function($app){
            // TODO: Добавить этот пункт в конфигурацию пакета
            return $app->make(CacheFactory::class)->store(
                'array'
            );
        });

        $this->app->singleton(LockProvider::class, function($app){
            $cache = $app->make(CacheRepository::class);
            return $cache->getStore();
        });

        $this->app->singleton(IdempotencyManager::class, function ($app) {
            return new IdempotencyManager($app);
        });

        $this->app->bind(CacheableSpecification::class, AlwaysCacheableSpecification::class);
        $this->app->singleton(IdempotencyConfig::class, function($app){
            return IdempotencyConfig::from(
                config('idempotency')
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    private function getCacheRepository()
    {

    }
}
