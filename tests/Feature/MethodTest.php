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

use Truschery\Idem\Exceptions\LockWaitExceededException;
use Truschery\Idem\Strategy\CacheIdempotencyStrategy;

describe('Method', function (){

    it('successfully executed the initial call, caches the response, and release the lock', function () {
        $record = new \Truschery\Idem\IdempotencyRecord;
        $key = 'idempotency-uuid';

        $mock = mock(CacheIdempotencyStrategy::class);
        $mock->shouldReceive('get')->twice()->with($key)->andReturn($record);
        $mock->shouldReceive('acquireLock')->once()->with($key)->andReturn(true);
        $mock->shouldReceive('save')->once();
        $mock->shouldReceive('releaseLock')->once()->with($key);

        $method = new \Truschery\Idem\Method($mock);

        $response = $method->deed($key, fn() => true);

        expect($response)->toBeTrue();
    });

    it('return the cached response on subsequent calls without re-executing the original logic', function (){
        $key = 'cache-idempotency-uuid';
        $count = 1;

        $record = new \Truschery\Idem\IdempotencyRecord(
            1,
            true
        );

        $mockStrategy = mock(CacheIdempotencyStrategy::class);
        $mockStrategy->shouldReceive('get')->once()->with($key)->andReturn($record);

        $mockStrategy->shouldReceive('acquireLock')->never();
        $mockStrategy->shouldReceive('save')->never();
        $mockStrategy->shouldReceive('releaseLock')->never();

        $method = new \Truschery\Idem\Method($mockStrategy);

        $response = $method->deed($key, function () use (&$count){
            return ++$count;
        });

        expect($count)->toBe(1)
        ->and($response)->toBe(1);
    });

    it('throws an exception when the timeout for acquiring a lock is exceeded', function () {
        $record = new \Truschery\Idem\IdempotencyRecord;
        $key = 'throw-idempotency-uuid';

        // TODO: Переписать тест как появится конфиг
        $mock = mock(CacheIdempotencyStrategy::class);
        $mock->shouldReceive('get')->with($key)->andReturn($record);
        $mock->shouldReceive('acquireLock')->with($key)->andReturn(false);

        $method = new \Truschery\Idem\Method($mock);

        expect(fn() => $method->deed($key, fn() => throw new Exception(), 1))
            ->toThrow(LockWaitExceededException::class);
    });

    it('waits for the lock to end and returns the cached result', function (){
        $record = new \Truschery\Idem\IdempotencyRecord(
            'response',
            true
        );
        $key = 'wait-idempotency-uuid';
        $mock = mock(CacheIdempotencyStrategy::class);
        $mock->shouldReceive('get')->with($key)->andReturn($record);
        $mock->shouldReceive('acquireLock')->with($key)->andReturn(false);

        $method = new \Truschery\Idem\Method($mock);

        $response = $method->deed($key, fn() => 'second response');
        expect($response)->toBe($record->response);
    });
});