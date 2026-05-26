<?php

namespace Truschery\Idem;

use Truschery\Idem\ValueObjects\Key;
use Truschery\Idem\ValueObjects\Record;

class Once
{
    public function do(string|Key $key, \Closure $callback): Record
    {
        $idempotencyKey = $key instanceof Key ? $key : new Key($key);

        return Method::factory()->deed($idempotencyKey, $callback);
    }
}
