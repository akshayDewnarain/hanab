<?php

namespace Domain\Employees\QueryBuilders;

use Domain\Employees\Helpers\CertificateHelper;
use Domain\Support\QueryBuilders\BaseBuilder;

class CertificateQueryBuilder extends BaseBuilder
{
    public function helper(): string
    {
        return CertificateHelper::class;
    }
}
