<?php

use Truschery\Idem\Tests\Fixtures\JobProcesser;
use Truschery\Idem\Tests\Fixtures\TestJob;
use Truschery\Idem\ValueObjects\Key;

describe('Ensure Idempotency', function () {

    it('test job', function () {

        $key = new Key(
            Str::uuid()->toString()
        );

        $spy = mock(JobProcesser::class);
        $spy->shouldReceive('execute')->once();
        $this->app->instance(JobProcesser::class, $spy);

        $job = new TestJob($key->key);

        dispatch_sync($job);
        dispatch_sync($job);
    });

});
