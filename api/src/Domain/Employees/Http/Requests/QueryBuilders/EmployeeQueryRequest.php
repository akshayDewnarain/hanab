<?php

namespace Domain\Employees\Http\Requests\QueryBuilders;

use Domain\Employees\Helpers\EmployeeHelper;
use Domain\Employees\Models\Employee;
use Domain\Support\Http\Requests\QueryBuilders\BaseModelQueryRequest;
use Domain\Support\Http\Requests\QueryBuilders\BaseQueryBuilder;

abstract class EmployeeQueryRequest extends BaseModelQueryRequest
{
    public function queryBuilder(): BaseQueryBuilder
    {
        return $this->baseQueryBuilder(Employee::class)
            ->defaultSort('-id')
            ->allowedFilters(EmployeeHelper::filterable())
            ->allowedIncludes(EmployeeHelper::relations())
            ->allowedSorts(EmployeeHelper::sortable())
            ->search(EmployeeHelper::searchable());
    }
}
