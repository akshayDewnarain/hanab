<?php

namespace Domain\Employees\Http\Requests\QueryBuilders;

use Domain\Employees\Helpers\EmployeeLocationHelper;
use Domain\Employees\Models\EmployeeLocation;
use Domain\Support\Http\Requests\QueryBuilders\BaseModelQueryRequest;
use Domain\Support\Http\Requests\QueryBuilders\BaseQueryBuilder;

abstract class EmployeeLocationQueryRequest extends BaseModelQueryRequest
{
    public function queryBuilder(): BaseQueryBuilder
    {
        return $this->baseQueryBuilder(EmployeeLocation::class)
            ->defaultSort('-id')
            ->allowedFilters(EmployeeLocationHelper::filterable())
            ->allowedIncludes(EmployeeLocationHelper::relations())
            ->allowedSorts(EmployeeLocationHelper::sortable())
            ->search(EmployeeLocationHelper::searchable());
    }
}
