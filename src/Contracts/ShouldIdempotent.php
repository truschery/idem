<?php

namespace Truschery\Idem\Contracts;

interface ShouldIdempotent
{
    public function idempotencyKey(): string;
}
