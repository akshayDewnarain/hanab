<?php

namespace Domain\Employees\Http\Resources;

use Domain\Employees\Models\EmployeeSkill;
use Domain\Support\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/** @property-read EmployeeSkill $resource */
abstract class EmployeeSkillResource extends BaseResource
{
    public function toArray(Request $request)
    {
        return $this->withAttributes([
            'employee_id',
            'skill_id',
            'level',
            'notes',
            'assessed_at',
        ]);
    }
}
