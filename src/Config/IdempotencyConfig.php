<?php

namespace Truschery\Idem\Config;

final readonly class IdempotencyConfig
{

    private const LOCK_WAIT_TIMEOUT = 10;
    private const LOCK_WAIT_STRATEGY = 'relay';
    private const REQUEST_IDEMPOTENT_METHODS = ['POST', 'PATCH'];
    private const REQUEST_STORE = 'cache';
    private const REQUEST_HEADER_IDEMPOTENCY_KEY_NAME = 'Idempotency-Key';
    private const REQUEST_HEADER_IDEMPOTENCY_RELAY_NAME = 'Idempotency-Relay';
    private const CACHE_STORE = 'array';


    public function __construct(
        public int $lockWaitTimeout,
        public string $lockWaitStrategy,
        public array $requestIdempotentMethods,
        public string $requestStore,
        public string $requestHeaderIdempotencyKeyName,
        public string $requestHeaderIdempotencyRelayName,
        public string $cacheStore
    )
    {
    }

    public static function from(?array $config = null): IdempotencyConfig
    {
//        return new self(
//            lockWaitTimeout: $config['lock'],
//            $config['lock_wait_strategy'],
//
//        );
    }


}