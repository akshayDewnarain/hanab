<?php

namespace Domain\Support\Transfer;

use RuntimeException;

class TransferModelNotFound extends RuntimeException
{
    public function __construct(public readonly string $modelKey)
    {
        parent::__construct("Unknown transfer model [{$modelKey}].");
    }
}
