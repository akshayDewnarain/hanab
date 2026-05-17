<?php

namespace Application\API\Employees\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class EmployeeSkillResourceCollection extends ResourceCollection
{
    /** {@inheritdoc} */
    public $collects = EmployeeSkillResource::class;
}
