<?php

namespace Truschery\Idem\Providers;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\ServiceProvider;
use Truschery\Idem\Config\IdempotencyConfig;
use Truschery\Idem\Console\IdempotencyPruneCommand;
use Truschery\Idem\Contracts\CacheableSpecification;
use Truschery\Idem\Contracts\IdempotencyStore;
use Truschery\Idem\Middleware\Idempotent;
use Truschery\Idem\Specs\AlwaysCacheableSpecification;
use Truschery\Idem\Stores\CacheStore;
use Truschery\Idem\Stores\DatabaseStore;

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
        $this->publishMigrations();
        $this->publishConfig();
        $this->registerMiddleware();
        $this->registerCommands();
    }

    private function registerMiddleware(): void
    {
        $router = $this->app['router'];
        $config = $this->app->make(IdempotencyConfig::class);

        $router->aliasMiddleware(
            $config->requestMiddlewareAlias,
            Idempotent::class
        );
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/idempotency.php', 'idempotency');
    }

    private function publishConfig(): void
    {
        if($this->app->runningInConsole()){
            $this->publishes([
                dirname(__DIR__, 2).'/config/idempotency.php' => config_path('idempotency.php'),
            ], 'idem-config');
        }
    }

    private function publishMigrations(): void
    {
        if($this->app->runningInConsole()){
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'idem-migrations');
        }
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

        $this->app->bind(CacheableSpecification::class, AlwaysCacheableSpecification::class);
        $this->app->bind(IdempotencyStore::class, function($app){
            $config = $app->make(IdempotencyConfig::class);
            return match ($config->defaultStore){
                'cache' => $app->make(CacheStore::class),
                'database' => $app->make(DatabaseStore::class),
                default => throw new \Exception('Unknown Idempotency Store Driver: ' . $config->defaultStore),
            };
        });
    }

    private function registerCommands()
    {
        if($this->app->runningInConsole()){
            $this->commands([
                IdempotencyPruneCommand::class
            ]);
        }
    }
}
