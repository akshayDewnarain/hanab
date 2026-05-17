<?php

namespace Domain\Employees\Http\Requests\QueryBuilders;

use Domain\Employees\Helpers\EmployeeCertificateHelper;
use Domain\Employees\Models\EmployeeCertificate;
use Domain\Support\Http\Requests\QueryBuilders\BaseModelQueryRequest;
use Domain\Support\Http\Requests\QueryBuilders\BaseQueryBuilder;

abstract class EmployeeCertificateQueryRequest extends BaseModelQueryRequest
{
    public function queryBuilder(): BaseQueryBuilder
    {
        return $this->baseQueryBuilder(EmployeeCertificate::class)
            ->defaultSort('-id')
            ->allowedFilters(EmployeeCertificateHelper::filterable())
            ->allowedIncludes(EmployeeCertificateHelper::relations())
            ->allowedSorts(EmployeeCertificateHelper::sortable())
            ->search(EmployeeCertificateHelper::searchable());
    }
}
