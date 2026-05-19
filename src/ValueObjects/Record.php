<?php

namespace Truschery\Idem\ValueObjects;

use Truschery\Idem\Enums\Status;

readonly class Record
{
    public function __construct(
        public ?Status $status = null,
        public mixed $response = null,
        public ?string $hash = null,
    ){}
}
