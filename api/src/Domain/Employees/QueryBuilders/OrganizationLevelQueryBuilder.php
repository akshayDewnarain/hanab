<?php

namespace Domain\Employees\QueryBuilders;

use Domain\Employees\Helpers\OrganizationLevelHelper;
use Domain\Support\QueryBuilders\BaseBuilder;

class OrganizationLevelQueryBuilder extends BaseBuilder
{
    public function helper(): string
    {
        return OrganizationLevelHelper::class;
    }
}
