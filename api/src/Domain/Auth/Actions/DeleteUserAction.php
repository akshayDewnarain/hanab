<?php

namespace Domain\Auth\Actions;

use Domain\Auth\Exceptions\LastSuperAdminException;
use Domain\Auth\Models\User;
use Domain\Auth\Support\SuperAdminGuard;
use Domain\Support\Actions\BaseAction;

class DeleteUserAction extends BaseAction
{
    public function execute(User $model): bool
    {
        if (SuperAdminGuard::isLastActive($model)) {
            throw new LastSuperAdminException();
        }

        $model->tokens()->delete();

        return (bool) $model->delete();
    }
}
