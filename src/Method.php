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
    ) {}

    public static function factory(
        ?CacheableSpecification $cacheableSpecification = null
    ): self {
        $spec = $cacheableSpecification ?: app(CacheableSpecification::class);
        $store = app()->make(IdempotencyStore::class);

        return new self($store, $spec);
    }

    /**
     * @throws IdempotencyHashMismatchException
     */
    public function deed(Key $key, \Closure $callback): Record
    {
        $record = $this->store->get($key);
        if ($record->status === Status::COMPLETED) {
            return $this->relay($key, $record);
        }

        $lock = $this->store->acquireLock($key);

        return match ($lock) {
            LockState::ACQUIRED => $this->executeAndStore($key, $callback),
            LockState::LOCKED => $this->waitAndRelay($key, $callback),
            LockState::COMPLETED => function () use ($key, $record) {
                $record = $this->store->get($key);
                if ($record->status === Status::COMPLETED) {
                    return $this->relay($key, $record);
                }
            }
        };
    }

    private function executeAndStore(Key $key, \Closure $callback): Record
    {
        try {
            $response = $callback();

            if ($this->cacheableSpecification->isSatisfiedBy($response)) {
                return $this->store->save($key, $response);
            }

            return new Record(
                Status::COMPLETED,
                $response
            );
        } finally {
            $this->store->releaseLock($key);
        }
    }

    /**
     * @throws IdempotencyHashMismatchException
     */
    private function waitAndRelay(Key $key, \Closure $callback): Record
    {
        $this->store->waitForLock($key);

        $record = $this->store->get($key);
        if ($record->status === Status::COMPLETED) {
            return $this->relay($key, $record);
        }

        return $this->executeAndStore($key, $callback);
    }

    /**
     * @throws IdempotencyHashMismatchException
     */
    private function relay(Key $key, Record $record): Record
    {
        if ($record->hash && $record->hash !== $key->hash) {
            throw new IdempotencyHashMismatchException('Mismatch hashes');
        }

        return $record;
    }
}
