<?php

namespace Application\API\Auth\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UserResourceCollection extends ResourceCollection
{
    /** {@inheritdoc} */
    public $collects = UserResource::class;
}
