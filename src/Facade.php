<?php

namespace Truschery\Idem;

use Truschery\Idem\ValueObjects\Key;

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

    public function run(string|Key $key, \Closure $callback)
    {
        $idempotencyKey = $key instanceof Key ? $key : new Key($key);
        return $this->manager->driver()->deed($idempotencyKey, $callback);
    }

}