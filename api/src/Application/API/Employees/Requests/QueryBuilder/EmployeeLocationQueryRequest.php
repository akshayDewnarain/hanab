<?php

namespace Application\API\Employees\Requests\QueryBuilder;

use Application\API\Support\QueryBuilders\ApiQueryBuilder;
use Domain\Employees\Http\Requests\QueryBuilders\EmployeeLocationQueryRequest as DomainEmployeeLocationQueryRequest;

class EmployeeLocationQueryRequest extends DomainEmployeeLocationQueryRequest
{
    protected function queryClass(): mixed
    {
        return ApiQueryBuilder::class;
    }
}
