<?php

namespace Application\API\Employees\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class EmployeeRoleResourceCollection extends ResourceCollection
{
    /** {@inheritdoc} */
    public $collects = EmployeeRoleResource::class;
}
