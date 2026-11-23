<?php

namespace Application\API\Employees\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class OrganizationLevelResourceCollection extends ResourceCollection
{
    /** {@inheritdoc} */
    public $collects = OrganizationLevelResource::class;
}
