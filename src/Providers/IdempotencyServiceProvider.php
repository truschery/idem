<?php

namespace Truschery\Idem\Providers;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\ServiceProvider;
use Truschery\Idem\Contracts\IdempotencyStrategyInterface;
use Truschery\Idem\IdempotencyManager;
use Truschery\Idem\Method;
use Truschery\Idem\Strategy\CacheIdempotencyStrategy;

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
