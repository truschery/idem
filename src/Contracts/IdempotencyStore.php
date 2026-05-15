<?php

namespace Truschery\Idem\Contracts;

use Truschery\Idem\IdempotencyKey;
use Truschery\Idem\IdempotencyRecord;

interface IdempotencyStore
{
    public function get(IdempotencyKey $key): IdempotencyRecord;

    public function save(IdempotencyKey $key, $response): IdempotencyRecord;

    public function acquireLock(IdempotencyKey $key): bool;

    public function waitForLock(IdempotencyKey $key): void;

    public function releaseLock(IdempotencyKey $key);


}
