<?php

namespace Truschery\Idem\ValueObjects;

class Key
{
    public function __construct(
        public readonly string $key,
        public readonly ?string $hash = null
    ){}
}