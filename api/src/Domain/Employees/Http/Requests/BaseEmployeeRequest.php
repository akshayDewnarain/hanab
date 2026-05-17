<?php

namespace Domain\Employees\Http\Requests;

use Domain\Employees\Models\Employee;
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
            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function model(): mixed
    {
        return Employee::class;
    }
}
