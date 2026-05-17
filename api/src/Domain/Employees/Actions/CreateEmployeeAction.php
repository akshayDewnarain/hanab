<?php

namespace Domain\Employees\Actions;

use Domain\Employees\DataTransferObjects\EmployeeData;
use Domain\Employees\Models\Employee;
use Domain\Support\Actions\BaseAction;
use Illuminate\Http\UploadedFile;

class CreateEmployeeAction extends BaseAction
{
    public function execute(EmployeeData $data): Employee
    {
        $model = new Employee;
        $model->fill($data->all());
        $model->save();

        if ($data->image instanceof UploadedFile) {
            $model->addMedia($data->image)->toMediaCollection('image');
        }

        if ($data->hasRelationalIds('skill_ids')) {
            $model->syncSkillIds($data->skill_ids ?? []);
        }

        if ($data->hasRelationalIds('certificate_ids')) {
            $model->certificates()->sync($data->certificate_ids ?? []);
        }

        return $model;
    }
}
