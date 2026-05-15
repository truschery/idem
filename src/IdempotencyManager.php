<?php

namespace Truschery\Idem;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Manager;
use Truschery\Idem\Contracts\IdempotencyStore;
use Truschery\Idem\Stores\CacheStore;

class IdempotencyManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return 'cache';
    }

    /**
     * @throws BindingResolutionException
     */
    public function createCacheDriver(): IdempotencyStore
    {
        return $this->container->make(CacheStore::class);
    }
}
