<?php

namespace App\Exceptions;

use RuntimeException;

class DuffelException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly array $errors = [],
    ) {
        parent::__construct($message, $status);
    }
}
