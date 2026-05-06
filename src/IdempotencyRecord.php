<?php

namespace Truschery\Idem;

class IdempotencyRecord
{
    public function __construct(
        public string $key,
        public readonly mixed $response,
        public readonly bool $hit,
    ){}
}
