<?php

namespace Domain\Employees\Http\Requests\QueryBuilders;

use Domain\Employees\Helpers\CertificateHelper;
use Domain\Employees\Models\Certificate;
use Domain\Support\Http\Requests\QueryBuilders\BaseModelQueryRequest;
use Domain\Support\Http\Requests\QueryBuilders\BaseQueryBuilder;

abstract class CertificateQueryRequest extends BaseModelQueryRequest
{
    public function queryBuilder(): BaseQueryBuilder
    {
        return $this->baseQueryBuilder(Certificate::class)
            ->defaultSort('-id')
            ->allowedFilters(CertificateHelper::filterable())
            ->allowedIncludes(CertificateHelper::relations())
            ->allowedSorts(CertificateHelper::sortable())
            ->search(CertificateHelper::searchable());
    }
}
