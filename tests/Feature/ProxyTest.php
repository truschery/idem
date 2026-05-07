<?php

/**
 * Проверяем класс Proxy, цель которого проксировать методы объекта
 * 1. Передан объект у которого имеется атрибут Idempotent у метода
 * Критерий успеха:
 */


describe('Proxy', function (){

    it('handles Idempotent attribute correctly', function (){
        $obj = new class {
            #[\Truschery\Idem\Attributes\Idempotent]
            public function execute()
            {}
        };

        $manager = mock(\Truschery\Idem\IdempotencyManager::class);
        $manager->shouldReceive('driver->deed')->once()->andReturn(true);
        $proxy = \Truschery\Idem\Proxy::make(
            $obj,
            $manager,
            'idempotency-key',
        );

        $proxy->execute();
    });

    it('handles IdempotencyKey attribute correctly', function (){
        $obj = new class {
            #[\Truschery\Idem\Attributes\Idempotent]
            public function execute(
                #[\Truschery\Idem\Attributes\IdempotencyKey]
                $id
            ){}
        };

        $manager = mock(\Truschery\Idem\IdempotencyManager::class);
        $manager->shouldReceive('driver->deed')->once()->andReturn(true);
        $proxy = \Truschery\Idem\Proxy::make(
            $obj,
            $manager
        );

        $proxy->execute('id');
    });

    it('throw if not provided attribute IdempotencyKey and idempotency-key from make', function (){
        $obj = new class {
            #[\Truschery\Idem\Attributes\Idempotent]
            public function execute(
            ){}
        };

        $manager = mock(\Truschery\Idem\IdempotencyManager::class);
        $manager->shouldReceive('driver->deed')->never();
        $proxy = \Truschery\Idem\Proxy::make(
            $obj,
            $manager
        );

        expect(fn() => $proxy->execute())->toThrow(Exception::class);
    });

});