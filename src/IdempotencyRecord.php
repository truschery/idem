<?php

namespace Truschery\Idem;

class IdempotencyRecord
{
    public function __construct(
        public readonly mixed $response = null,
        public bool $isReplayed = false,
    ){}

    public function markAsReplayed(): void
    {
        $this->isReplayed = true;
    }
}
