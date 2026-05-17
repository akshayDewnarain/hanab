<?php

namespace Application\API\Employees\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class EmployeeResourceCollection extends ResourceCollection
{
    /** {@inheritdoc} */
    public $collects = EmployeeResource::class;
}
