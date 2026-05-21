<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Truschery\Idem\Config\IdempotencyConfig;
use Truschery\Idem\Contracts\IdempotencyStore;
use Truschery\Idem\Exceptions\IdempotencyHashMismatchException;
use Truschery\Idem\Exceptions\ConcurrentInvocationException;
use Truschery\Idem\Stores\CacheStore;
use Truschery\Idem\Stores\DatabaseStore;
use Truschery\Idem\ValueObjects\Key;

describe('Middleware Idempotent', function() {
    beforeEach(function() {
       $this->app->forgetInstance(IdempotencyConfig::class);
    });

    it('can process single request and successful handle idempotency', function (string $storeName){
        bindStoreClass($storeName, $this->app);
        $key = new Key(Str::uuid());
        $response = $this->post('/idempotent', headers: [
            'Idempotency-Key' => $key->key
        ]);

        $store = app()->make($storeName);
        $record = $store->get($key);
        $response->assertStatus(200);

        expect($record->response)->toBeInstanceOf(JsonResponse::class)
        ->and($record->response->getData(true)['timestamp'])->toBe($response->json('timestamp'));
    });

    it('return the cached response on subsequent request without re-executing the original logic', function (string $storeName){
        bindStoreClass($storeName, $this->app);
        $idempotencyKey = Str::uuid()->toString();
        $responseFirst = $this->post('/idempotent', headers: [
            'Idempotency-Key' => $idempotencyKey
        ]);

        $responseFirst->assertStatus(200);
        $responseFirst->assertHeaderMissing('Idempotency-Relay');

        $responseSecond = $this->post('/idempotent', headers: [
            'Idempotency-Key' => $idempotencyKey
        ]);

        $responseSecond->assertStatus(200);
        $responseSecond->assertHeader('Idempotency-Relay');

        expect($responseFirst->json('timestamp'))
            ->toBe($responseSecond->json('timestamp'));
    });

    it('throws an exception when "lock_wait.strategy" equals "exception"', function (string $storeName){

        $this->withoutExceptionHandling();
        updateIdempotencyConfig($this->app, [
            'idempotency.lock_wait.strategy' => 'exception',
            'idempotency.lock_wait.timeout' => 1
        ]);
        bindStoreClass($storeName, $this->app);

        $key = new \Truschery\Idem\ValueObjects\Key(
            Str::uuid()->toString(),
        );

        $store = app()->make($storeName);
        $store->acquireLock($key);

        $this->expectException(ConcurrentInvocationException::class);
        $this->expectExceptionCode(409);
        $this->post('/idempotent', headers: [
            'Idempotency-Key' => $key->key
        ]);
    });

    it('return the cached response on concurrent request when "lock_wait.strategy" equals "wait"', function (string $storeName){
        updateIdempotencyConfig($this->app, [
            'idempotency.lock_wait.strategy' => 'wait',
            'idempotency.lock_wait.timeout' => 1,
        ]);
        bindStoreClass($storeName, $this->app);

        $key = new \Truschery\Idem\ValueObjects\Key(
            Str::uuid()->toString(),
        );

        $store = $this->app->make($storeName);
        $store->acquireLock($key);
        $timestamp = microtime(true);
        $store->save($key, response()->json([
            'timestamp' => $timestamp,
        ]));

        updateIdempotencyConfig($this->app, [
            'idempotency.lock_wait.timeout' => 2,
        ]);
        $response = $this->post('/idempotent', headers: [
            'Idempotency-Key' => $key->key
        ]);
        $response->assertOk();
        expect($response->json('timestamp'))->toBe($timestamp);
    });

    it('throws an exception when the timeout for acquiring a lock is exceeded', function (string $storeName) {
        updateIdempotencyConfig($this->app, [
            'idempotency.lock_wait.timeout' => 1
        ]);
        bindStoreClass($storeName, $this->app);
        $this->withoutExceptionHandling();


        $key = new \Truschery\Idem\ValueObjects\Key(
          Str::uuid()->toString(),
        );

        $store = app()->make($storeName);
        $store->acquireLock($key);

        updateIdempotencyConfig($this->app, [
            'idempotency.lock_wait.timeout' => 0
        ]);

        $this->expectException(\Truschery\Idem\Exceptions\LockWaitExceededException::class);
        $this->post('/idempotent', headers: [
            'Idempotency-Key' => $key->key
        ]);
    });

    it('throws an exception when request hash mismatch', function (string $storeName){
        bindStoreClass($storeName, $this->app);
        $this->withoutExceptionHandling();
        $key = new \Truschery\Idem\ValueObjects\Key(
            Str::uuid()->toString(),
        );

        $responseFirst = $this->post('/idempotent', headers: [
            'Idempotency-Key' => $key->key,
            'Expect-Header' => 'true'
        ]);

        $responseFirst->assertStatus(200);

        $this->expectException(IdempotencyHashMismatchException::class);
        $this->post('/idempotent', headers: [
            'Idempotency-Key' => $key->key,
        ]);
    });

    it('does not save the request to the cache if a server error occurs.', function (string $storeName) {
        bindStoreClass($storeName, $this->app);
        $key = new \Truschery\Idem\ValueObjects\Key(
            Str::uuid()->toString(),
        );

        $this->post('/idempotent-500', headers: [
            'Idempotency-Key' => $key->key,
        ])->assertStatus(500);

        $store = app()->make($storeName);
        $record = $store->get($key);

        expect($record->response)->toBeNull();
    });

    it('saves a new request after the ttl expires', function (string $storeName){
        bindStoreClass($storeName, $this->app);
        $key = new \Truschery\Idem\ValueObjects\Key(
            Str::uuid()->toString(),
        );

        updateIdempotencyConfig($this->app, [
            'idempotency.stores.cache.ttl' => 0,
            'idempotency.stores.database.ttl' => 0,
        ]);
        $responseFirst = $this->post('/idempotent', headers: [
            'Idempotency-Key' => $key->key,
        ])->assertOk();

        $responseSecond = $this->post('/idempotent', headers: [
            'Idempotency-Key' => $key->key,
        ])->assertOk();

        expect($responseFirst->json('timestamp'))->toBeLessThan($responseSecond->json('timestamp'));
    });
})->with([
    'Cache Store' => CacheStore::class,
    'Database Store' => DatabaseStore::class,
]);