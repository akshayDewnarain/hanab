<?php

namespace Domain\Employees\Actions;

use Domain\Employees\Models\EmployeeCertificate;
use Domain\Support\Actions\BaseAction;

class DeleteEmployeeCertificateAction extends BaseAction
{
    public function execute(EmployeeCertificate $model): bool
    {
        return $model->delete();
    }
}
