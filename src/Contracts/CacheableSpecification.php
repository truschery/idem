<?php

namespace Truschery\Idem\Contracts;

interface CacheableSpecification
{
    public function isSatisfiedBy(mixed $response): bool;
}