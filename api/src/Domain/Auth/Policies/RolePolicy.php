<?php

namespace Domain\Auth\Policies;

use Domain\Auth\Models\Role;
use Domain\Support\Policies\BasePolicy;

class RolePolicy extends BasePolicy
{
    protected function model(): string
    {
        return Role::class;
    }
}
