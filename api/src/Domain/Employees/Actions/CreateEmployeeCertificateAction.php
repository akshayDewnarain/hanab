<?php

namespace Domain\Employees\Actions;

use Domain\Employees\DataTransferObjects\EmployeeCertificateData;
use Domain\Employees\Models\EmployeeCertificate;
use Domain\Messages\Actions\RecordCertificateMessageAction;
use Domain\Support\Actions\BaseAction;

class CreateEmployeeCertificateAction extends BaseAction
{
    public function execute(EmployeeCertificateData $data): EmployeeCertificate
    {
        $model = new EmployeeCertificate;
        $model->fill($data->all());
        $model->save();
        RecordCertificateMessageAction::make()->execute($model);

        return $model;
    }
}
