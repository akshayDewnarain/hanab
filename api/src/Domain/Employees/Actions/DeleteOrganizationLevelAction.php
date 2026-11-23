<?php

namespace Domain\Employees\Actions;

use Domain\Employees\Models\OrganizationLevel;
use Domain\Support\Actions\BaseAction;

class DeleteOrganizationLevelAction extends BaseAction
{
    public function execute(OrganizationLevel $model): bool
    {
        return $model->delete();
    }
}
