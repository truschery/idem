<?php

namespace Truschery\Idem\Middleware;

use Illuminate\Http\Request;
use Truschery\Idem\Exceptions\IdempotencyKeyMismatchException;
use Truschery\Idem\IdempotencyKey;
use Truschery\Idem\IdempotencyManager;
use Truschery\Idem\Method;
use Truschery\Idem\Specs\HttpCacheableSpecification;
use Truschery\Kanon\Json;

class Idempotent
{
    public function __construct(
        private IdempotencyManager $manager
    )
    {
    }

    public function handle(Request $request, \Closure $next)
    {
        // TODO(idea): Вынести в конфигурацию
        // какие методы запросов проверять
        // какой способ(cache, database) использовать для запросов
        // название для Idempotency-Key
        if($request->isMethodSafe()){
            return $next($request);
        }

        $strategy = $this->manager->driver();

        $idempotencyKey = new IdempotencyKey(
            key: $request->header('Idempotency-Key'),
            hash: Json::canonicalize(json_decode($request->getContent(), true))
        );

        return Method::factory(
            $strategy,
            app()->make(HttpCacheableSpecification::class),
        )->deed($idempotencyKey, fn () => $next($request));
    }
}