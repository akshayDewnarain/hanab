<?php

namespace Domain\Employees\Actions;

use Domain\Employees\DataTransferObjects\EmployeeCertificateData;
use Domain\Employees\Models\EmployeeCertificate;
use Domain\Support\Actions\BaseAction;

class UpdateEmployeeCertificateAction extends BaseAction
{
    public function execute(EmployeeCertificate $model, EmployeeCertificateData $data): EmployeeCertificate
    {
        $model->update($data->getUpdateData($model));

        return $model;
    }
}
