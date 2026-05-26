<?php

namespace Truschery\Idem\Stores;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Truschery\Idem\Config\IdempotencyConfig;
use Truschery\Idem\Contracts\IdempotencyStore;
use Truschery\Idem\Enums\LockState;
use Truschery\Idem\Enums\Status;
use Truschery\Idem\Exceptions\ConcurrentInvocationException;
use Truschery\Idem\Exceptions\LockWaitExceededException;
use Truschery\Idem\ValueObjects\Key;
use Truschery\Idem\ValueObjects\Record;

class DatabaseStore implements IdempotencyStore
{
    public function __construct(
        private IdempotencyConfig $config,
    ) {}

    public function get(Key $key): Record
    {
        $row = DB::table($this->config->databaseTable)
            ->where('key', $key->key)
            ->lockForUpdate()
            ->first();

        if (! $row || is_null($row->response)) {
            return new Record;
        }
        if ($this->idemIsExpired($row)) {
            return new Record;
        }

        return new Record(
            Status::from($row->status),
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
                'status' => Status::COMPLETED->value,
            ]);

        return new Record(
            Status::COMPLETED,
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

            if (! $row) {
                DB::table($this->config->databaseTable)
                    ->insert([
                        'key' => $key->key,
                        'hash' => $key->hash,
                        'status' => Status::PROCESSING->value,
                        'response' => null,
                        'expires_at' => Carbon::now()->addSeconds($this->config->databaseTtl)->timestamp,
                    ]);
                DB::commit();

                return LockState::ACQUIRED;
            }

            if ($this->idemIsExpired($row)) {
                DB::table($this->config->databaseTable)
                    ->where('key', $key->key)
                    ->update([
                        'hash' => $key->hash,
                        'response' => null,
                        'status' => Status::COMPLETED->value,
                        'expires_at' => Carbon::now()->addSeconds($this->config->databaseTtl)->timestamp,
                    ]);

                DB::commit();

                return LockState::ACQUIRED;
            }

            DB::commit();

            if ($row->status === Status::COMPLETED->value) {
                return LockState::COMPLETED;
            }

            return LockState::LOCKED;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function waitForLock(Key $key): void
    {
        if ($this->config->lockWaitStrategy === 'exception') {
            throw new ConcurrentInvocationException;
        }

        $interval = 100;
        $elapsed = 0;

        while ($elapsed < $this->config->lockWaitTimeout * 1000) {
            $record = $this->get($key);

            if ($record->status === Status::COMPLETED->value) {
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
        DB::table($this->config->databaseTable)
            ->where('key', $key->key)
            ->where('status', Status::PROCESSING->value)
            ->delete();

        return true;
    }

    private function idemIsExpired(\stdClass $row): bool
    {
        return $row->expires_at <= Carbon::now()->timestamp;
    }
}
