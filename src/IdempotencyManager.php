<?php

namespace Truschery\Idem;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Manager;
use Truschery\Idem\Config\IdempotencyConfig;
use Truschery\Idem\Contracts\IdempotencyStore;
use Truschery\Idem\Stores\CacheStore;

class IdempotencyManager extends Manager
{
    public function __construct(
        Container $container,
    )
    {
        parent::__construct($container);
    }

    /**
     * @throws BindingResolutionException
     */
    public function getDefaultDriver(): string
    {
        return $this->container->make(IdempotencyConfig::class)->defaultStore;
    }

    /**
     * @throws BindingResolutionException
     */
    public function createCacheDriver(): IdempotencyStore
    {
        return $this->container->make(CacheStore::class);
    }
}
