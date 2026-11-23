<?php

namespace Domain\Auth\Exceptions;

use RuntimeException;

class LastSuperAdminException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The last active administrator cannot be removed or disabled.');
    }
}
