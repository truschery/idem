<?php

namespace Truschery\Idem;

use Truschery\Idem\Contracts\CacheableSpecification;
use Truschery\Idem\Contracts\IdempotencyStore;
use Truschery\Idem\Enums\LockState;
use Truschery\Idem\Enums\Status;
use Truschery\Idem\Exceptions\IdempotencyHashMismatchException;
use Truschery\Idem\ValueObjects\Key;
use Truschery\Idem\ValueObjects\Record;

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
     * @throws IdempotencyHashMismatchException
     */
    public function deed(Key $key, \Closure $callback): Record
    {
        $record = $this->store->get($key);
        if($record->status === Status::COMPLETED){
            return $this->onRelay($key, $record);
        }

        // TODO: Сделать условие на получение lock
        $lock = $this->store->acquireLock($key);

        if($lock === LockState::LOCKED){
            $this->store->waitForLock($key);
        }

        if($lock === LockState::COMPLETED){
            $record = $this->store->get($key);

            if($record->isReplayed){
                return $this->onRelay($key, $record);
            }
        }

        try {
            $response = $callback();

            if($this->cacheableSpecification->isSatisfiedBy($response)){
                return $this->store->save($key, $response);
            }

            return new Record(
                $response
            );
        } finally {
            $this->store->releaseLock($key);
        }
    }

    /**
     * @throws IdempotencyHashMismatchException
     */
    private function onRelay(Key $key, Record $record): Record
    {
        if($record->hash && $record->hash !== $key->hash){
            throw new IdempotencyHashMismatchException('Mismatch hashes');
        }

        return $record;
    }

}