<?php

namespace Domain\Employees\Http\Requests;

use Domain\Employees\Models\EmployeeRole;
use Domain\Support\Http\Requests\BaseModelRequest;
use Illuminate\Validation\Rule;

abstract class BaseEmployeeRoleRequest extends BaseModelRequest
{
    public function rules(): array
    {
        $role = $this->route('employee_role');
        $role = $role instanceof EmployeeRole ? $role : null;

        $nameUnique = Rule::unique('employee_roles', 'name');
        if ($role !== null) {
            $nameUnique->ignore($role->id);
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                $nameUnique,
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function model(): mixed
    {
        return EmployeeRole::class;
    }
}
