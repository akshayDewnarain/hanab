<?php

namespace Domain\Employees\Actions;

use Domain\Employees\DataTransferObjects\CertificateData;
use Domain\Employees\Models\Certificate;
use Domain\Support\Actions\BaseAction;

class UpdateCertificateAction extends BaseAction
{
    public function execute(Certificate $model, CertificateData $data): Certificate
    {
        $model->update($data->getUpdateData($model));

        return $model;
    }
}
