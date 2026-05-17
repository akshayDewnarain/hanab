<?php

namespace Application\API\Employees\Requests\QueryBuilder;

use Application\API\Support\QueryBuilders\ApiQueryBuilder;
use Domain\Employees\Http\Requests\QueryBuilders\CertificateQueryRequest as DomainCertificateQueryRequest;

class CertificateQueryRequest extends DomainCertificateQueryRequest
{
    protected function queryClass(): mixed
    {
        return ApiQueryBuilder::class;
    }
}
