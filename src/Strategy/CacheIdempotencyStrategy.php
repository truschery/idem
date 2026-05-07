<?php

namespace Truschery\Idem\Strategy;

use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Truschery\Idem\IdempotencyRecord;
use Psr\SimpleCache\InvalidArgumentException;
use Truschery\Idem\Contracts\IdempotencyStrategyInterface;

class CacheIdempotencyStrategy implements IdempotencyStrategyInterface
{

    private string $lockOwner;

    public function __construct(
        private readonly LockProvider $lockProvider,
        private readonly Cacherepository $cacheRepository
    ){}

    const PREFIX = 'idempotency:strategy:';
    const LOCK_SECONDS = 10;

    /**
     * @param string $key
     * @return IdempotencyRecord
     * @throws InvalidArgumentException
     */
    public function get(string $key): IdempotencyRecord
    {
        $response = $this->cacheRepository->get($this->getCacheKey($key));
        if(is_null($response)){
            return new IdempotencyRecord;
        }

        return new IdempotencyRecord($response, true);
    }

    /**
     * @param string $key
     * @param mixed $response
     * @return IdempotencyRecord
     * @throws InvalidArgumentException
     */
    public function save(string $key, mixed $response): IdempotencyRecord
    {
        $this->cacheRepository->set($this->getCacheKey($key), $response);
        return new IdempotencyRecord($response);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function acquireLock(string $key): bool
    {
        try {
            $lock = $this->lockProvider
                ->lock(
                    $this->getCacheKey($key),
                    // TODO: Добавить пункт в конфиг
                    self::LOCK_SECONDS,
                );

            $this->lockOwner = $lock->owner();

            return $lock
            ->block(self::LOCK_SECONDS);
        }catch (LockTimeoutException $e){
            return false;
        }
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function releaseLock(string $key): bool
    {
        $restore = $this->lockProvider
            ->restoreLock($this->getCacheKey($key), $this->lockOwner);

        return $restore->release();
    }

    private function getCacheKey(string $key): string
    {
        return self::PREFIX . $key;
    }
}
