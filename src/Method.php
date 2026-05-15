<?php

namespace Truschery\Idem;

use Truschery\Idem\Contracts\CacheableSpecification;
use Truschery\Idem\Contracts\IdempotencyPolicy;
use Truschery\Idem\Contracts\IdempotencyStore;
use Truschery\Idem\Exceptions\IdempotencyKeyMismatchException;
use Truschery\Idem\Exceptions\LockWaitExceededException;

class Method
{
    public function __construct(
        private IdempotencyStore $store,
        private CacheableSpecification $cacheableSpecification
    ){}

    public static function factory(
        IdempotencyStore $store,
        ?CacheableSpecification $cacheableSpecification = null
    )
    {
        $spec = $cacheableSpecification ?: app(CacheableSpecification::class);
        return new self($store, $spec);
    }

    /**
     * @throws LockWaitExceededException
     * @throws IdempotencyKeyMismatchException
     */
    public function deed(IdempotencyKey $key, \Closure $callback)
    {
        // TODO: Нужно еще реализовать проверку хеша параметров
        $record = $this->store->get($key);

        if($record->isReplayed){
            return $this->onRelay($key, $record);
        }

        $this->store->waitForLock($key);

        $record = $this->store->get($key);

        if($record->isReplayed){
            return $this->onRelay($key, $record);
        }

        try {
            $response = $callback();

            if($this->cacheableSpecification->isSatisfiedBy($response)){
                $this->store->save($key, $response);
            }

            return $response;
        } finally {
            $this->store->releaseLock($key);
        }
    }

    /**
     * @throws IdempotencyKeyMismatchException
     */
    private function onRelay(IdempotencyKey $key, IdempotencyRecord $record)
    {
        if($record->hash && $record->hash !== $key->hash){
            throw new IdempotencyKeyMismatchException('Mismatch hashes');
        }

        return $record->response;
    }

}