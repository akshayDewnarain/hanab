<?php

namespace Application\API\Employees\Controllers\Actions;

use Domain\Employees\Models\Employee;
use Domain\Support\Http\Controllers\BaseDeleteImageActionController;

class DeleteEmployeeImageActionController extends BaseDeleteImageActionController
{
    protected string $param = 'employee';

    protected function modelClass(): string
    {
        return Employee::class;
    }
}
