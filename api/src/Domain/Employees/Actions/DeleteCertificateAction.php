<?php

namespace Domain\Employees\Actions;

use Domain\Employees\Models\Certificate;
use Domain\Support\Actions\BaseAction;

class DeleteCertificateAction extends BaseAction
{
    public function execute(Certificate $model): bool
    {
        return $model->delete();
    }
}
