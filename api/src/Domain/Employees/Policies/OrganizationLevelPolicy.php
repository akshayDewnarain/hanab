<?php

namespace Domain\Employees\Policies;

use Domain\Employees\Models\OrganizationLevel;
use Domain\Support\Policies\BasePolicy;

class OrganizationLevelPolicy extends BasePolicy
{
    protected function model(): string
    {
        return OrganizationLevel::class;
    }
}
