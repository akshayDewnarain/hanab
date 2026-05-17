<?php

namespace Domain\Employees\Policies;

use Domain\Employees\Models\Certificate;
use Domain\Support\Policies\BasePolicy;

class CertificatePolicy extends BasePolicy
{
    protected function model(): string
    {
        return Certificate::class;
    }
}
