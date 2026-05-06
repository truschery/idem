<?php

namespace Truschery\Idem;

class Once
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
    public function make(string|object $obj, ?string $key = null): object
    {
        return Proxy::make(
            $obj,
            $key,
            $this->manager
        );
    }

    // Использовать замыкание
    public function run(string $key, \Closure $callback)
    {
        return $this->manager->driver()->deed($key, $callback);
    }

    // Забыть ключ
    public function forget()
    {

    }

}