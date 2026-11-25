<?php

namespace Domain\Dashboard\Actions;

use Domain\Auth\Models\User;
use Domain\Dashboard\Services\DashboardActionProvider;
use Domain\Dashboard\Services\EmployeeDataQualityActionProvider;
use Domain\Dashboard\Services\WorkLocationActionProvider;
use Domain\Support\Actions\BaseAction;

class ListDashboardActionsAction extends BaseAction
{
    /**
     * @param  list<DashboardActionProvider>|null  $providers
     * @return list<array<string, mixed>>
     */
    public function execute(User $user, ?array $providers = null): array
    {
        $providers ??= [
            new EmployeeDataQualityActionProvider,
            new WorkLocationActionProvider,
        ];

        $actions = [];
        foreach ($providers as $provider) {
            foreach ($provider->provide($user) as $action) {
                if ($action->count <= 0) {
                    continue;
                }
                $actions[] = $action->toArray();
            }
        }

        return $actions;
    }
}
