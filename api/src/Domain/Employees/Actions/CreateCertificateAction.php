<?php

namespace Domain\Employees\Actions;

use Domain\Employees\DataTransferObjects\CertificateData;
use Domain\Employees\Models\Certificate;
use Domain\Support\Actions\BaseAction;

class CreateCertificateAction extends BaseAction
{
    public function execute(CertificateData $data): Certificate
    {
        $model = new Certificate;
        $model->fill($data->all());
        $model->save();

        return $model;
    }
}
