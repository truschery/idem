<?php

namespace Truschery\Idem\Stores;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Truschery\Idem\Config\IdempotencyConfig;
use Truschery\Idem\Contracts\IdempotencyStore;
use Truschery\Idem\Enums\LockState;
use Truschery\Idem\Exceptions\LockWaitExceededException;
use Truschery\Idem\Exceptions\ConcurrentInvocationException;
use Truschery\Idem\ValueObjects\Key;
use Truschery\Idem\ValueObjects\Record;

class DatabaseStore implements IdempotencyStore
{

    public function __construct(
        private IdempotencyConfig $config,
    )
    {
    }

    public function get(Key $key): Record
    {
        $row = DB::table($this->config->databaseTable)
            ->where('key', $key->key)
            ->lockForUpdate()
            ->first();

        if(!$row || is_null($row->response)) return new Record;
        if($this->idemIsExpired($row)) return new Record;

        return new Record(
            unserialize($row->response),
            $row->hash,
            true
        );
    }

    public function save(Key $key, $response): Record
    {
        DB::table($this->config->databaseTable)
            ->where('key', $key->key)
            ->update([
                'response' => serialize($response),
            ]);

        return new Record(
          $response,
          $key->hash
        );
    }

    /**
     * @throws \Throwable
     */
    public function acquireLock(Key $key): LockState
    {
        DB::beginTransaction();
        try {
            $row = DB::table($this->config->databaseTable)
                ->where('key', $key->key)
                ->lockForUpdate()
                ->first();

            if(! $row) {
                DB::table($this->config->databaseTable)
                    ->insert([
                        'key' => $key->key,
                        'hash' => $key->hash,
                        'response' => null,
                        'expires_at' => Carbon::now()->addSeconds($this->config->databaseTtl)->timestamp
                    ]);
                DB::commit();
                return LockState::ACQUIRED;
            }

            if($this->idemIsExpired($row))
            {
                DB::table($this->config->databaseTable)
                    ->where('key', $key->key)
                    ->update([
                        'hash' => $key->hash,
                        'response' => null,
                        'expires_at' => Carbon::now()->addSeconds($this->config->databaseTtl)->timestamp
                    ]);

                DB::commit();
                return LockState::ACQUIRED;
            }

            if(! is_null($row->response)){
                DB::commit();
                return LockState::COMPLETED;
            }

            DB::commit();
            return LockState::LOCKED;
        } catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }
    }

    public function waitForLock(Key $key): void
    {
        if($this->config->lockWaitStrategy === 'exception') throw new ConcurrentInvocationException;

        $interval = 100;
        $elapsed = 0;

        while($elapsed < $this->config->lockWaitTimeout * 1000) {
            $record = $this->get($key);

            if($record->response !== null){
                return;
            }

            usleep($interval * 1000);
            $elapsed += $interval;
            $interval = min($interval * 2, 1000);
        }

        throw new LockWaitExceededException;
    }

    public function releaseLock(Key $key): true
    {
        return true;
    }

    private function idemIsExpired(\stdClass $row): bool
    {
        return $row->expires_at <= Carbon::now()->timestamp;
    }
}