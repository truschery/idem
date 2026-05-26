<?php

namespace Truschery\Idem\Middleware;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Truschery\Idem\Config\IdempotencyConfig;
use Truschery\Idem\Exceptions\IdempotencyHashMismatchException;
use Truschery\Idem\Method;
use Truschery\Idem\Specs\HttpCacheableSpecification;
use Truschery\Idem\ValueObjects\Key;
use Truschery\Idem\ValueObjects\Record;
use Truschery\Kanon\Json;

class Idempotent
{
    public function __construct(
        private IdempotencyConfig $config,
    ) {}

    /**
     * @throws BindingResolutionException
     * @throws IdempotencyHashMismatchException
     */
    public function handle(Request $request, \Closure $next)
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $idempotencyKey = new Key(
            key: $request->header($this->config->requestHeaderIdempotencyKeyName),
            hash: $this->generateRequestHash($request)
        );

        $record = Method::factory(
            app()->make(HttpCacheableSpecification::class),
        )->deed($idempotencyKey, fn () => $next($request));

        $this->markRequestAsRelayed($record);

        return $record->response;
    }

    private function markRequestAsRelayed(Record $record): void
    {
        if (! $record->isReplayed) {
            return;
        }

        $record->response->header($this->config->requestHeaderIdempotencyRelayName, true);
    }

    private function shouldSkip(Request $request): bool
    {
        if (! in_array($request->method(), $this->config->requestIdempotentMethods)) {
            return true;
        }

        if (! $request->header($this->config->requestHeaderIdempotencyKeyName)) {
            return true;
        }

        return false;
    }

    private function generateRequestHash(Request $request): string
    {
        $fingerprint = [
            'content' => json_decode($request->getContent(), true),
            'method' => $request->method(),
            'path' => $request->path(),
        ];

        return hash(
            'sha256',
            Json::canonicalize($fingerprint)
        );
    }
}
