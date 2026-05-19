<?php

namespace Truschery\Idem\Stores;

use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\SimpleCache\InvalidArgumentException;
use Truschery\Idem\Config\IdempotencyConfig;
use Truschery\Idem\Contracts\IdempotencyStore;
use Truschery\Idem\Enums\Status;
use Truschery\Idem\Exceptions\LockWaitExceededException;
use Truschery\Idem\Exceptions\ConcurrentInvocationException;
use Truschery\Idem\ValueObjects\Key;
use Truschery\Idem\ValueObjects\Record;
use Truschery\Idem\Enums\LockState;

class CacheStore implements IdempotencyStore
{
    private string $lockOwner;

    public function __construct(
        private readonly LockProvider $lockProvider,
        private readonly Cacherepository $cacheRepository,
        private readonly IdempotencyConfig $config,
    ){}

    const PREFIX = 'idempotency:record:';

    /**
     * @param \Truschery\Idem\ValueObjects\Key $key
     * @return \Truschery\Idem\ValueObjects\Record
     * @throws InvalidArgumentException
     */
    public function get(Key $key): Record
    {
        $row = $this->cacheRepository->get($this->getCacheKey($key));
        if(is_null($row)){
            return new Record;
        }

        return new Record(
            status: Status::from($row['status']),
            response: $row['response'],
            hash: $row['hash'],
        );
    }

    /**
     * @param \Truschery\Idem\ValueObjects\Key $key
     * @param mixed $response
     * @return Record
     * @throws InvalidArgumentException
     */
    public function save(Key $key, mixed $response): Record
    {
        $this->cacheRepository->set($this->getCacheKey($key), [
            'response' => $response,
            'hash' => $key->hash,
            'status' => Status::COMPLETED->value,
        ], $this->config->cacheTtl);

        return new Record(
            status: Status::COMPLETED,
            response: $response,
            hash: $key->hash,
        );
    }

    /**
     * @param \Truschery\Idem\ValueObjects\Key $key
     * @return \Truschery\Idem\Enums\LockState
     */


    public function acquireLock(Key $key): LockState
    {
        try {
            $lock = $this->lockProvider
                ->lock(
                    $this->getCacheKey($key),
                    $this->config->lockWaitTimeout,
                );

            $this->lockOwner = $lock->owner();
            $lock->block($this->config->lockWaitTimeout);
            return LockState::ACQUIRED;
        }catch (LockTimeoutException $e){
            return LockState::LOCKED;
        }
    }

    /**
     * @param \Truschery\Idem\ValueObjects\Key $key
     * @return mixed
     */
    public function releaseLock(Key $key): bool
    {
        $restore = $this->lockProvider
            ->restoreLock($this->getCacheKey($key), $this->lockOwner);

        return $restore->release();
    }

    private function getCacheKey(Key $key): string
    {
        return self::PREFIX . $key->key;
    }

    /**
     * @throws LockWaitExceededException|ConcurrentInvocationException
     */
    public function waitForLock(Key $key): void
    {
        if($this->config->lockWaitStrategy === 'exception') throw new ConcurrentInvocationException;

        if($this->acquireLock($key) === LockState::LOCKED){
            throw new LockWaitExceededException;
        }
    }
}
