<?php

namespace Truschery\Idem\Exceptions;

use Throwable;

class ConcurrentInvocationException extends \Exception
{
    public function __construct(string $message = '', int $code = 409, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
