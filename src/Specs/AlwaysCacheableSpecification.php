<?php

namespace Truschery\Idem\Specs;

use Truschery\Idem\Contracts\CacheableSpecification;

class AlwaysCacheableSpecification implements CacheableSpecification
{
    public function isSatisfiedBy(mixed $response): bool
    {
        return true;
    }
}
