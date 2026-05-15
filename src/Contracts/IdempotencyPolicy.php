<?php

namespace Truschery\Idem\Contracts;

use Truschery\Idem\IdempotencyKey;
use Truschery\Idem\IdempotencyRecord;

interface IdempotencyPolicy
{
    public function onRelay(IdempotencyKey $key, IdempotencyRecord $record);
}