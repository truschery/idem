<?php

namespace Truschery\Idem;

class IdempotencyKey
{
    public function __construct(
        public readonly string $key,
        public readonly ?string $hash = null
    ){}
}