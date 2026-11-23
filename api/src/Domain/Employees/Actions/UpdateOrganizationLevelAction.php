<?php

namespace Domain\Employees\Actions;

use Domain\Employees\DataTransferObjects\OrganizationLevelData;
use Domain\Employees\Models\OrganizationLevel;
use Domain\Support\Actions\BaseAction;

class UpdateOrganizationLevelAction extends BaseAction
{
    public function execute(OrganizationLevel $model, OrganizationLevelData $data): OrganizationLevel
    {
        $model->update($data->getUpdateData($model));

        return $model;
    }
}
