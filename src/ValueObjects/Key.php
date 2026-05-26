<?php

namespace Truschery\Idem\ValueObjects;

readonly class Key
{
    public function __construct(
        public string $key,
        public ?string $hash = null
    ) {}
}
