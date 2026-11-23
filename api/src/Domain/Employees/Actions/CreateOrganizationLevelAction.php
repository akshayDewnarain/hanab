<?php

namespace Domain\Employees\Actions;

use Domain\Employees\DataTransferObjects\OrganizationLevelData;
use Domain\Employees\Models\OrganizationLevel;
use Domain\Support\Actions\BaseAction;

class CreateOrganizationLevelAction extends BaseAction
{
    public function execute(OrganizationLevelData $data): OrganizationLevel
    {
        $model = new OrganizationLevel;
        $model->fill($data->all());
        $model->save();

        return $model;
    }
}
