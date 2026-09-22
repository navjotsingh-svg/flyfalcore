<?php

namespace App\Exceptions;

use RuntimeException;

class PayPalException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly array $payload = [],
    ) {
        parent::__construct($message, $status);
    }
}
