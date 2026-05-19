<?php

namespace Truschery\Idem\Middleware;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Truschery\Idem\Config\IdempotencyConfig;
use Truschery\Idem\Contracts\IdempotencyStore;
use Truschery\Idem\Exceptions\LockWaitExceededException;
use Truschery\Idem\Exceptions\IdempotencyHashMismatchException;
use Truschery\Idem\IdempotencyManager;
use Truschery\Idem\Method;
use Truschery\Idem\Specs\HttpCacheableSpecification;
use Truschery\Idem\ValueObjects\Key;
use Truschery\Kanon\Json;

class Idempotent
{
    public function __construct(
        private IdempotencyManager $manager,
        private IdempotencyConfig $config,
    )
    {
    }

    /**
     * @throws LockWaitExceededException
     * @throws BindingResolutionException
     * @throws IdempotencyHashMismatchException
     */
    public function handle(Request $request, \Closure $next)
    {
        if($this->shouldSkip($request)){
            return $next($request);
        }

        $idempotencyKey = new Key(
            key: $request->header($this->config->requestHeaderIdempotencyKeyName),
            hash: $this->generateRequestHash($request)
        );

        $record = Method::factory(
            app()->make(IdempotencyStore::class),
            app()->make(HttpCacheableSpecification::class),
        )->deed($idempotencyKey, fn () => $next($request));

        $this->markRequestAsRelayed($record);

        return $record->response;
    }

    private function markRequestAsRelayed(\Truschery\Idem\ValueObjects\Record $record): void
    {
        if(!$record->isReplayed) return;

        // TODO: Добавить в конфигурацию
        $record->response->header($this->config->requestHeaderIdempotencyRelayName, true);
    }

    private function shouldSkip(Request $request): bool
    {
        return !in_array($request->method(), $this->config->requestIdempotentMethods);
    }

    private function generateRequestHash(Request $request): string
    {
        $fingerprint = [
            'content' => json_decode($request->getContent(), true),
            'headers' => $request->headers->all(),
            'path' => $request->path(),
        ];

        return hash(
            'sha256',
            Json::canonicalize($fingerprint)
        );
    }

}