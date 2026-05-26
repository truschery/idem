<?php

namespace Truschery\Idem\Contracts;

use Truschery\Idem\Enums\LockState;
use Truschery\Idem\ValueObjects\Key;
use Truschery\Idem\ValueObjects\Record;

interface IdempotencyStore
{
    public function get(Key $key): Record;

    public function save(Key $key, $response): Record;

    public function acquireLock(Key $key): LockState;

    public function waitForLock(Key $key): void;

    public function releaseLock(Key $key): bool;
}
