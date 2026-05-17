<?php

namespace Truschery\Idem\Specs;



use Illuminate\Http\Response;
use Truschery\Idem\Contracts\CacheableSpecification;

class HttpCacheableSpecification implements CacheableSpecification
{

    public function isSatisfiedBy(mixed $response): bool
    {
        return $response->isSuccessful();
    }
}