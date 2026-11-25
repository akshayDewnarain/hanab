<?php

namespace Domain\Dashboard\Services;

use Domain\Auth\Models\User;
use Domain\Dashboard\DataTransferObjects\DashboardAction;

interface DashboardActionProvider
{
    /**
     * @return list<DashboardAction>
     */
    public function provide(User $user): array;
}
