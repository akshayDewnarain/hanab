<?php

namespace Application\API\Employees\Requests\QueryBuilder;

use Application\API\Support\QueryBuilders\ApiQueryBuilder;
use Domain\Employees\Http\Requests\QueryBuilders\EmployeeCertificateQueryRequest as DomainEmployeeCertificateQueryRequest;

class EmployeeCertificateQueryRequest extends DomainEmployeeCertificateQueryRequest
{
    protected function queryClass(): mixed
    {
        return ApiQueryBuilder::class;
    }
}
