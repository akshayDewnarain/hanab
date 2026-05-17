<?php

namespace Application\API\Employees\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class EmployeeLocationResourceCollection extends ResourceCollection
{
    /** {@inheritdoc} */
    public $collects = EmployeeLocationResource::class;
}
