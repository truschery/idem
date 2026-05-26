<?php

namespace Truschery\Idem\Middleware;

use Truschery\Idem\Exceptions\IdempotencyHashMismatchException;
use Truschery\Idem\Method;
use Truschery\Idem\ValueObjects\Key;

class EnsureIdempotency
{
    public function __construct(
        private readonly string $idempotencyKey
    ) {}

    /**
     * @throws IdempotencyHashMismatchException
     */
    public function handle(object $job, \Closure $next): void
    {
        $key = new Key($this->idempotencyKey);

        Method::factory()
            ->deed(
                $key,
                fn () => $next($job)
            );
    }
}
