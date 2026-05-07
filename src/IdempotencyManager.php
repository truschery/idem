<?php

namespace Truschery\Idem;

use Illuminate\Support\Manager;
use Truschery\Idem\Contracts\IdempotencyStrategyInterface;
use Truschery\Idem\Strategy\CacheIdempotencyStrategy;

class IdempotencyManager extends Manager
{
    public function getDefaultDriver()
    {
        return 'cache';
    }

    public function createCacheDriver()
    {
        return $this->container->make(Method::class, [
            'strategy' => app()->make(CacheIdempotencyStrategy::class),
        ]);
    }
}
