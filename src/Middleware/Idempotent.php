<?php

namespace Truschery\Idem\Middleware;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Truschery\Idem\Exceptions\IdempotencyKeyMismatchException;
use Truschery\Idem\Exceptions\LockWaitExceededException;
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

    /**
     * @throws LockWaitExceededException
     * @throws BindingResolutionException
     * @throws IdempotencyKeyMismatchException
     */
    public function handle(Request $request, \Closure $next)
    {
        // TODO(idea): Вынести в конфигурацию
        // какие методы запросов проверять
        // какой способ(cache, database) использовать для запросов
        // название для Idempotency-Key
        if($request->isMethodSafe()){
            return $next($request);
        }

        $idempotencyKey = new IdempotencyKey(
            key: $request->header('Idempotency-Key'),
            hash: Json::canonicalize(json_decode($request->getContent(), true))
        );

        $record = Method::factory(
            $this->manager->driver(),
            app()->make(HttpCacheableSpecification::class),
        )->deed($idempotencyKey, fn () => $next($request));

        $this->markRequestAsRelayed($record);

        return $record->response;
    }

    private function markRequestAsRelayed(\Truschery\Idem\IdempotencyRecord $record): void
    {
        if(!$record->isReplayed) return;

        // TODO: Добавить в конфигурацию
        $record->response->header('Idempotency-Relayed', true);
    }

}