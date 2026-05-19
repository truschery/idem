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
        $this->registerConfig();
        $this->registerBindings();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrations();
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/idempotency.php', 'idempotency');
    }

    private function registerBindings(): void
    {
        $this->app->singleton(IdempotencyConfig::class, function($app){
            return IdempotencyConfig::from(
                $app->config->get('idempotency')
            );
        });
        $this->app->singleton(CacheRepository::class, function($app){
            $config = $app->make(IdempotencyConfig::class);
            return $app->make(CacheFactory::class)->store(
                $config->getCacheDriver()
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
    }

    private function loadMigrations(): void
    {
        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations')
        ]);
    }
}
