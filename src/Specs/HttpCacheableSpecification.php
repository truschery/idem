<?php

namespace Truschery\Idem\Specs;

use Truschery\Idem\Contracts\CacheableSpecification;

class HttpCacheableSpecification implements CacheableSpecification
{
    public function isSatisfiedBy(mixed $response): bool
    {
        if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
            return $response->isSuccessful();
        }

        return true;
    }
}
