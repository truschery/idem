<div align="center">

<img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel">

# Laravel Idempotency

**Idempotency for HTTP requests, queued jobs, and arbitrary operations**

[![Latest Version on Packagist](https://img.shields.io/packagist/v/truschery/idem.svg?style=flat-square)](https://packagist.org/packages/truschery/idem)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue?style=flat-square)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012-red?style=flat-square)](https://laravel.com)
[![License](https://img.shields.io/packagist/l/truschery/idem?style=flat-square)](LICENSE)

</div>

---

Ensures that repeating the same operation always produces the same result with no side effects. Essential for payment systems, APIs, and any operation where duplication is unacceptable.

Full documentation is available at [idem.truschery.dev](https://idem.truschery.dev)

## Features

- **`Idempotent`** — HTTP middleware: a repeated request with the same `Idempotency-Key` returns the cached response
- **`EnsureIdempotent`** — Job middleware: a queued job is executed only once, even if dispatched multiple times
- **`Once::do()`** — facade for arbitrary operations: any callable runs exactly once per key
- **Two storage drivers**: `cache` and `database`

## Installation

```bash
composer require truschery/idem
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=idem-config
```

If you plan to use the `database` driver, publish and run the migrations:

```bash
php artisan vendor:publish --tag=idem-migrations
php artisan migrate
```

## Usage

### HTTP Requests — `Idempotent`

Register the middleware alias and apply it to a route or group:

```php

// Routes
Route::post('/payments', [PaymentController::class, 'store'])
    ->middleware('idempotent');
```

The client sends a unique key in the request header:

```http
POST /payments HTTP/1.1
Idempotency-Key: 550e8400-e29b-41d4-a716-446655440000
Content-Type: application/json

{"amount": 1000, "currency": "USD"}
```

A repeated request with the same key returns the stored response without re-executing the handler.

> If the `Idempotency-Key` header is absent, the request is processed normally — no error is thrown.

---

### Queued Jobs — `EnsureIdempotent`

Pass the idempotency key directly to the middleware constructor:

```php
use Truschery\Idem\Middleware\EnsureIdempotent;

class ProcessPaymentJob implements ShouldQueue
{
    public function __construct(private string $paymentId) {}

    public function middleware(): array
    {
        return [new EnsureIdempotent($this->paymentId)];
    }

    public function handle(): void
    {
        // Runs only once, even if the job is dispatched multiple times
        Payment::process($this->paymentId);
    }
}
```

---

### Arbitrary Operations — `Once::do()`

```php
use Truschery\Idem\Once;

$result = Once::do('send-welcome-email:' . $user->id, function () use ($user) {
    return Mail::to($user)->send(new WelcomeMail($user));
});
```

A repeated call with the same key returns the cached result of the first execution.

`Once::do()` works in any context — HTTP requests, queued jobs, Artisan commands.

## Roadmap

- [x] HTTP Middleware (`Idempotent`)
- [x] Job Middleware (`EnsureIdempotent`)
- [x] `Once::do()` facade
- [x] `cache` and `database` drivers
- [x] Artisan command `idem:prune` — removes expired records from the database
- [ ] Extended tests for Job middleware and `Once::do()`

## License

MIT — see [LICENSE](LICENSE) for details.