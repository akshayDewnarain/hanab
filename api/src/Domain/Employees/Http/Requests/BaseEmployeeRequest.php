<?php

namespace Domain\Employees\Http\Requests;

use Domain\Employees\Models\Employee;
use Domain\Employees\Support\EmployeeReporting;
use Domain\Support\Http\Requests\BaseModelRequest;
use Illuminate\Validation\Rule;

abstract class BaseEmployeeRequest extends BaseModelRequest
{
    public function rules(): array
    {
        $employee = $this->route('employee');
        $employee = $employee instanceof Employee ? $employee : null;

        $emailUnique = Rule::unique('employees', 'email');
        $numberUnique = Rule::unique('employees', 'employee_number');
        if ($employee !== null) {
            $emailUnique->ignore($employee);
            $numberUnique->ignore($employee);
        }

        return [
            'employee_role_id' => [
                'required',
                'integer',
                Rule::exists('employee_roles', 'id'),
            ],
            'employee_location_id' => [
                'required',
                'integer',
                Rule::exists('employee_locations', 'id'),
            ],
            'manager_id' => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id'),
            ],
            'organization_level_id' => [
                'nullable',
                'integer',
                Rule::exists('organization_levels', 'id'),
            ],
            'first_name' => [
                'required',
                'string',
                'max:255',
            ],
            'last_name' => [
                'required',
                'string',
                'max:255',
            ],
            'initials' => [
                'nullable',
                'string',
                'max:32',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                $emailUnique,
            ],
            'phone' => [
                'nullable',
                'string',
                'max:255',
            ],
            'business_email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'business_phone' => [
                'required',
                'string',
                'max:255',
            ],
            'introduction' => [
                'nullable',
                'string',
                'max:500',
            ],
            'employee_number' => [
                'required',
                'string',
                'max:255',
                $numberUnique,
            ],
            'employment_type' => [
                'required',
                'string',
                Rule::in(['internal', 'external']),
            ],
            'start_date' => [
                'nullable',
                'date',
            ],
            'date_of_birth' => [
                'nullable',
                'date',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $empty = [];
        foreach (['manager_id', 'organization_level_id', 'phone', 'business_email'] as $field) {
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
            $employee = $this->route('employee');
            $managerId = $this->input('manager_id');
            if (! $employee instanceof Employee || $managerId === null || $managerId === '') {
                return;
            }

            if ((int) $managerId === $employee->id) {
                $validator->errors()->add('manager_id', 'An employee cannot report to themselves.');

                return;
            }

            if (EmployeeReporting::createsCycle($employee, (int) $managerId)) {
                $validator->errors()->add('manager_id', 'This reporting line would loop back to the same employee.');
            }
        });
    }

    public function model(): mixed
    {
        return Employee::class;
    }
}
