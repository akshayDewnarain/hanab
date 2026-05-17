<?php

namespace Domain\Employees\Http\Requests;

use Domain\Employees\Models\EmployeeSkill;
use Domain\Support\Http\Requests\BaseModelRequest;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

abstract class BaseEmployeeSkillRequest extends BaseModelRequest
{
    public function rules(): array
    {
        $employeeSkill = $this->route('employee_skill');
        $employeeSkill = $employeeSkill instanceof EmployeeSkill ? $employeeSkill : null;

        $pairUnique = Rule::unique('employee_skills', 'employee_id')
            ->where(fn (Builder $query): Builder => $query->where(
                'skill_id',
                $this->input('skill_id')
            ));
        if ($employeeSkill !== null) {
            $pairUnique->ignore($employeeSkill->id);
        }

        return [
            'skill_id' => [
                'required',
                'integer',
                Rule::exists('skills', 'id'),
            ],
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id'),
                $pairUnique,
            ],
            'level' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
            'assessed_at' => [
                'nullable',
                'date',
            ],
        ];
    }

    public function model(): mixed
    {
        return EmployeeSkill::class;
    }
}
