<?php

namespace Truschery\Idem;

class IdempotencyRecord
{
    public function __construct(
        public readonly mixed $response = null,
        public readonly ?string $hash = null,
        public bool $isReplayed = false,
    ){}
}
