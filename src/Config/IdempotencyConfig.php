<?php

namespace Truschery\Idem\Config;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

final readonly class IdempotencyConfig
{
    public function __construct(
        public int $lockWaitTimeout,
        public string $lockWaitStrategy,
        public array $requestIdempotentMethods,
        public string $requestStore,
        public string $requestHeaderIdempotencyKeyName,
        public string $requestHeaderIdempotencyRelayName,
        public string $defaultStore,
        public string $cacheDriver,
        public int $cacheTtl,
        public int $databaseTtl,
        public string $databaseTable,
    )
    {
    }

    public static function from(?array $config = null): IdempotencyConfig
    {
        return new self(
            lockWaitTimeout: Arr::get($config, 'lock_wait.timeout'),
            lockWaitStrategy: Arr::get($config, 'lock_wait.strategy'),
            requestIdempotentMethods: Arr::get($config, 'request.idempotent_methods'),
            requestStore: Arr::get($config, 'request.store'),
            requestHeaderIdempotencyKeyName: Arr::get($config, 'request.header.idempotency_key'),
            requestHeaderIdempotencyRelayName: Arr::get($config, 'request.header.idempotency_relay'),
            defaultStore: Arr::get($config, 'default'),
            cacheDriver: Arr::get($config, 'stores.cache.driver'),
            cacheTtl: Arr::get($config, 'stores.cache.ttl'),
            databaseTtl: Arr::get($config, 'stores.database.ttl'),
            databaseTable: Arr::get($config, 'stores.database.table_name'),
        );
    }

    public function getCacheDriver(): ?string
    {
        if($this->cacheDriver === 'default') return null;
        return $this->cacheDriver;
    }


}