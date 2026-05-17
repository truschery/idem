<?php

use Illuminate\Support\Str;
use Truschery\Idem\Contracts\IdempotencyStore;
use Truschery\Idem\Stores\CacheStore;

describe('Middleware Idempotent', function() {

    beforeEach(function() {

    });

    it('can process single request and successful handle idempotency', function (){

        $response = $this->post('/idempotent', headers: [
            'Idempotency-Key' => Str::uuid()
        ]);

        $response->assertStatus(200);
    });

    it('return the cached response on subsequent calls without re-executing the original logic', function (){

        $idempotencyKey = Str::uuid()->toString();
        $responseFirst = $this->post('/idempotent', headers: [
            'Idempotency-Key' => $idempotencyKey
        ]);

        $responseFirst->assertStatus(200);
        $responseFirst->assertHeaderMissing('Idempotency-Relayed');

        $responseSecond = $this->post('/idempotent', headers: [
            'Idempotency-Key' => $idempotencyKey
        ]);

        $responseSecond->assertStatus(200);
        $responseSecond->assertHeader('Idempotency-Relayed');

        expect($responseFirst->json('timestamp'))
            ->toBe($responseSecond->json('timestamp'));
    });


    // TODO: Чтобы полноценно протестировать, нужно иметь config
    it('throws an exception when the timeout for acquiring a lock is exceeded', function () {
//        $key = new \Truschery\Idem\IdempotencyKey(
//          Str::uuid()->toString(),
//        );
//
//        $store = app(CacheStore::class);
//        $store->acquireLock($key);
//
//        $response = $this->post('/idempotent', headers: [
//            'Idempotency-Key' => $key->key
//        ]);
    });



});