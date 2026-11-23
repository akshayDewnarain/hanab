<?php

namespace Domain\Employees\Http\Requests;

use Domain\Employees\Models\EmployeeRole;
use Domain\Employees\Support\EmployeeRoleTree;
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
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('employee_roles', 'id'),
            ],
            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999',
            ],
            'organization_level_id' => [
                'nullable',
                'integer',
                Rule::exists('organization_levels', 'id'),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $empty = [];
        foreach (['parent_id', 'organization_level_id'] as $field) {
            if ($this->input($field) === '') {
                $empty[$field] = null;
            }
        }
        if ($empty !== []) {
            $this->merge($empty);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $role = $this->route('employee_role');
            $parentId = $this->input('parent_id');
            if (! $role instanceof EmployeeRole || $parentId === null || $parentId === '') {
                return;
            }

            if (EmployeeRoleTree::createsCycle($role, (int) $parentId)) {
                $validator->errors()->add('parent_id', 'A role cannot sit under itself.');
            }
        });
    }

    public function model(): mixed
    {
        return EmployeeRole::class;
    }
}
