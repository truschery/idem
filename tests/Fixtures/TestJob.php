<?php

namespace Truschery\Idem\Tests\Fixtures;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Truschery\Idem\Contracts\ShouldIdempotent;
use Truschery\Idem\Middleware\EnsureIdempotency;

class TestJob implements ShouldQueue, ShouldIdempotent
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        private string $key,
    )
    {
    }

    public function middleware(): array
    {
        return [
            new EnsureIdempotency,
        ];
    }

    public function idempotencyKey(): string
    {
        return $this->key;
    }

    public function handle(JobProcesser $spy): void
    {
        $spy->execute();
    }
}