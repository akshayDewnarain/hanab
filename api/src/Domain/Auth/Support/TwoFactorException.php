<?php

namespace Domain\Auth\Support;

use RuntimeException;

class TwoFactorException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 422,
    ) {
        parent::__construct($message);
    }
}
