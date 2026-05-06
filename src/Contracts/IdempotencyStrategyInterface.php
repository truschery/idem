<?php

namespace Truschery\Idem\Contracts;

use Truschery\Idem\IdempotencyRecord;

interface IdempotencyStrategyInterface
{
    public function get(string $key): IdempotencyRecord;

    public function save(string $key, $response): IdempotencyRecord;

    public function acquireLock(string $key): bool;

    public function releaseLock(string $key);



}
