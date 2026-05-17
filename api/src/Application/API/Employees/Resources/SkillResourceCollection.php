<?php

namespace Application\API\Employees\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SkillResourceCollection extends ResourceCollection
{
    /** {@inheritdoc} */
    public $collects = SkillResource::class;
}
