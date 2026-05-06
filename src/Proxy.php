<?php

namespace Truschery\Idem;

use Illuminate\Support\Traits\ForwardsCalls;
use ReflectionClass;
use Truschery\Idem\Attributes\IdempotencyKey;
use Truschery\Idem\Attributes\Idempotent;
use WeakMap;

class Proxy
{
    use ForwardsCalls;

    private array $hashedMethods = [];

    public function __construct(
        private object $instance,
        private ?string $idempotencyKey = null,
        private $idempotencyManager
        // config
    ){
        $this->gatherMarkedMethods();
    }

    /**
     * @template T of object
     * @param T $obj
     * @param string|null $key
     * @return T
     */
    public static function make(object $obj, ?string $key = null, IdempotencyManager $manager): object
    {
        return new self(
            $obj,
            $key,
            $manager
        );
    }

    public function __get($value)
    {
        return $this->instance->$value;
    }

    public function __set($property, $value)
    {
        return $this->instance->$property = $value;
    }

    public function __call(string $name, array $arguments)
    {
        if(! array_key_exists($name, $this->hashedMethods)){
            return $this->instance->$name(...$arguments);
        }

        if(!$this->idempotencyKey && ! isset($this->hashedMethods[$name])) throw new \Exception('Idempotency key required');

        $key = $this->idempotencyKey ?? $arguments[$this->hashedMethods[$name]];

        return $this->idempotencyManager->driver()->deed(
            $key,
            fn() => $this->instance->$name(...$arguments)
        );
    }


    private function gatherMarkedMethods(): void
    {
        $reflection = new ReflectionClass($this->instance);

        foreach ($reflection->getMethods() as $method) {
            if(empty($method->getAttributes(Idempotent::class))) continue;

            $idempotencyKeyPosition = null;
            foreach ($method->getParameters() as $param) {
                if(empty($param->getAttributes(IdempotencyKey::class))) continue;

                $idempotencyKeyPosition = $param->getPosition();
                break;
            }

            $this->hashedMethods[$method->getName()] = $idempotencyKeyPosition;
        }
    }
}