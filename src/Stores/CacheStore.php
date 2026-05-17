<?php

namespace Truschery\Idem\Stores;

use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Truschery\Idem\Config\IdempotencyConfig;
use Truschery\Idem\Exceptions\LockWaitExceededException;
use Truschery\Idem\IdempotencyKey;
use Truschery\Idem\IdempotencyRecord;
use Psr\SimpleCache\InvalidArgumentException;
use Truschery\Idem\Contracts\IdempotencyStore;

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
     * @param IdempotencyKey $key
     * @return IdempotencyRecord
     * @throws InvalidArgumentException
     */
    public function get(IdempotencyKey $key): IdempotencyRecord
    {
        $record = $this->cacheRepository->get($this->getCacheKey($key));
        if(is_null($record)){
            return new IdempotencyRecord;
        }

        return new IdempotencyRecord(
            response: $record['response'],
            hash: $record['hash'],
            isReplayed: true
        );
    }

    /**
     * @param IdempotencyKey $key
     * @param mixed $response
     * @return IdempotencyRecord
     * @throws InvalidArgumentException
     */
    public function save(IdempotencyKey $key, mixed $response): IdempotencyRecord
    {
        $this->cacheRepository->set($this->getCacheKey($key), [
            'response' => $response,
            'hash' => $key->hash
        ]);
        return new IdempotencyRecord($response);
    }

    /**
     * @param IdempotencyKey $key
     * @return bool
     */


    public function acquireLock(IdempotencyKey $key): bool
    {
        try {
            $lock = $this->lockProvider
                ->lock(
                    $this->getCacheKey($key),
                    $this->config->lockWaitTimeout,
                );

            $this->lockOwner = $lock->owner();

            return $lock
            ->block($this->config->lockWaitTimeout);
        }catch (LockTimeoutException $e){
            return false;
        }
    }

    /**
     * @param IdempotencyKey $key
     * @return mixed
     */
    public function releaseLock(IdempotencyKey $key): bool
    {
        $restore = $this->lockProvider
            ->restoreLock($this->getCacheKey($key), $this->lockOwner);

        return $restore->release();
    }

    private function getCacheKey(IdempotencyKey $key): string
    {
        return self::PREFIX . $key->key;
    }

    /**
     * @throws LockWaitExceededException
     */
    public function waitForLock(IdempotencyKey $key): void
    {
        if(! $this->acquireLock($key)){
            throw new LockWaitExceededException;
        }
    }
}
