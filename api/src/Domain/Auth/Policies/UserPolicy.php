<?php

namespace Domain\Auth\Policies;

use Domain\Auth\Models\User;
use Domain\Support\Policies\BasePolicy;

class UserPolicy extends BasePolicy
{
    protected function model(): string
    {
        return User::class;
    }
}
