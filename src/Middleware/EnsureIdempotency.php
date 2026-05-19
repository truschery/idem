<?php

namespace Truschery\Idem\Middleware;

use Truschery\Idem\Contracts\ShouldIdempotent;
use Truschery\Idem\IdempotencyManager;
use Truschery\Idem\Method;
use Truschery\Idem\ValueObjects\Key;

class EnsureIdempotency
{

    public function __construct(
    )
    {
    }

    public function handle(ShouldIdempotent $job, \Closure $next): void
    {
        $key = new Key(
            $job->idempotencyKey()
        );

        Method::factory(
            app()->make(IdempotencyManager::class)->driver(),
        )->deed(
            $key,
            fn() => $next($job)
        );
    }
    
}