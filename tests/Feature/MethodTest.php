<?php

/**
 * Описание возможных тестов
 * Тестируем функционал идемпотенции метода. Нужно проверить -
 * 1. Проверяем первый вызов функции, все методы должны пройти без ошибок
 * критерий успеха: метод вернул ожидаемый результат, в кеше имеется запись с ключом, блокировка ключа завершена
 * 2. Проверяем второй вызов функции
 * критерий успеха: метод должен вернуть ответ сохранившийся в кеше, методы интерфейса не должны быть вызваны
 * 3. Проверяем что второй вызов функции попадает в блокировку
 * критерий успеха: метод ожидает блокировки и вызывает Exception по истечению времени
 * 4. Проверяем что второй вызовы функции попадает в блокировку и дожидается блокировки
 * критерий успеха: метод ожидает блокировки и занимает блокировку, вызывает получения данные и возвращает результат
 * 5. Проверяем что вызов метода с ответами разных типов данных не ломает сериализацию
 * критерий успеха: метод корректно возвращает данные
 */

use Truschery\Idem\Contracts\IdempotencyStore;
use Truschery\Idem\Exceptions\LockWaitExceededException;
use Truschery\Idem\Method;
use Truschery\Idem\Stores\CacheStore;
use Truschery\Idem\Stores\DatabaseStore;
use Truschery\Idem\ValueObjects\Key;


describe('Method', function () {

    beforeEach(function () {
        $this->key = new Key('idempotency-key');
        $this->app->forgetInstance(\Truschery\Idem\Config\IdempotencyConfig::class);
    });

    it('successfully executed the initial call, caches the response, and release the lock', function (string $storeName) {
        bindStoreClass($storeName, $this->app);
        $method = Method::factory();

        $record = $method->deed($this->key, fn() => true);

        expect($record->response)->toBeTrue();
    });

    it('return the cached response on subsequent calls without re-executing the original logic', function (string $storeName){
        bindStoreClass($storeName, $this->app);
        $method = Method::factory();

        $count = 0;
        $method->deed($this->key, function () use (&$count){
            return ++$count;
        });

        $record = $method->deed($this->key, function () use (&$count){
            return ++$count;
        });

        expect($count)->toBe(1)
            ->and($record->response)->toBe(1);
    });

    it('throws an exception when the timeout for acquiring a lock is exceeded', function (string $storeName) {
        bindStoreClass($storeName, $this->app);
        updateIdempotencyConfig($this->app, [
            'idempotency.lock_wait.timeout' => 1
        ]);

        $store = $this->app->make($storeName);
        $store->acquireLock($this->key);

        updateIdempotencyConfig($this->app, [
            'idempotency.lock_wait.timeout' => 0
        ]);
        $method = Method::factory();

        $this->expectException(LockWaitExceededException::class);
        $method->deed($this->key, fn() => true);
    });

    it('return the cached response on concurrent call when "lock_wait.strategy" equals "wait"', function (string $storeName){
        bindStoreClass($storeName, $this->app);
        updateIdempotencyConfig($this->app, [
            'idempotency.lock_wait.strategy' => 'wait',
            'idempotency.lock_wait.timeout' => 0
        ]);

        $store = $this->app->make($storeName);
        $store->acquireLock($this->key);
        $store->save($this->key, 'first response');

        updateIdempotencyConfig($this->app, [
            'idempotency.lock_wait.timeout' => 1
        ]);

        $record = Method::factory()
            ->deed($this->key, fn() => 'second response');

        expect($record->response)->toBe('first response');
    });

    it('throws an exception when "lock_wait.strategy" equals "exception"', function (string $storeName){
        bindStoreClass($storeName, $this->app);
        updateIdempotencyConfig($this->app, [
            'idempotency.lock_wait.strategy' => 'exception',
            'idempotency.lock_wait.timeout' => 1
        ]);
        $store = $this->app->make($storeName);
        $store->acquireLock($this->key);

        $method = Method::factory();

        $this->expectException(\Truschery\Idem\Exceptions\ConcurrentInvocationException::class);
        $method->deed($this->key, fn() => 'response');
    });

    it('throws an exception when request hash mismatch', function (string $storeName){
        bindStoreClass($storeName, $this->app);
        $this->withoutExceptionHandling();
        $key = new Key(
            'throw-mismatch-idempotency-uuid',
            'hash-mismatch-idempotency-uuid'
        );

        $method = Method::factory();
        $method->deed($key, fn() => true);

        $this->expectException(\Truschery\Idem\Exceptions\IdempotencyHashMismatchException::class);
        $method->deed(new Key('throw-mismatch-idempotency-uuid'), fn() => true);
    });


    it('saves a new response after the ttl expires', function(string $storeName){
        bindStoreClass($storeName, $this->app);
        updateIdempotencyConfig($this->app, [
            'idempotency.stores.cache.ttl' => 0,
            'idempotency.stores.database.ttl' => 0,
        ]);

        $method = Method::factory();
        $count = 0;
        $responseFirst = $method->deed($this->key, function() use(&$count) {
            return ++$count;
        });
        $responseSecond = $method->deed($this->key, function() use(&$count) {
            return ++$count;
        });

        expect($responseFirst->response)->toBe(1)
            ->and($responseSecond->response)->toBe(2);
    });

//    it('can ');
})->with([
    CacheStore::class,
    DatabaseStore::class,
]);