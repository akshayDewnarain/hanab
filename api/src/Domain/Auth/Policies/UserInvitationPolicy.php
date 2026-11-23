<?php

namespace Domain\Auth\Policies;

use Domain\Auth\Models\UserInvitation;
use Domain\Support\Policies\BasePolicy;

class UserInvitationPolicy extends BasePolicy
{
    protected function model(): string
    {
        return UserInvitation::class;
    }
}
