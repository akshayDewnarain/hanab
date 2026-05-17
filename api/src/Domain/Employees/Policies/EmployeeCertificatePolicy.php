<?php

namespace Domain\Employees\Policies;

use Domain\Employees\Models\EmployeeCertificate;
use Domain\Support\Policies\BasePolicy;

class EmployeeCertificatePolicy extends BasePolicy
{
    protected function model(): string
    {
        return EmployeeCertificate::class;
    }
}
