<?php

namespace Domain\Employees\QueryBuilders;

use Domain\Employees\Helpers\EmployeeCertificateHelper;
use Domain\Support\QueryBuilders\BaseBuilder;

class EmployeeCertificateQueryBuilder extends BaseBuilder
{
    public function helper(): string
    {
        return EmployeeCertificateHelper::class;
    }
}
