<?php

namespace Truschery\Idem;

class Facade
{

    private IdempotencyManager $manager;

    public function __construct()
    {
        $this->manager = app()->make(IdempotencyManager::class);
    }

    /**
     * @template T of object
     * @param T $obj
     * @param string|null $key
     * @return T
     */
    public function make(object $obj, ?string $key = null): object
    {
        return Proxy::make(
            $obj,
            $this->manager,
            $key,
        );
    }

    public function run(string $key, \Closure $callback)
    {
        $idempotencyKey = new IdempotencyKey(
            key: $key,
        );
        return $this->manager->driver()->deed($key, $callback);
    }

    // Забыть ключ
    public function forget()
    {

    }

}