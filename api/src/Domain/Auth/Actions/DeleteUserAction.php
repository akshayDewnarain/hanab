<?php

namespace Domain\Auth\Actions;

use Domain\Auth\Models\User;
use Domain\Support\Actions\BaseAction;

class DeleteUserAction extends BaseAction
{
    public function execute(User $model): bool
    {
        return (bool) $model->delete();
    }
}
