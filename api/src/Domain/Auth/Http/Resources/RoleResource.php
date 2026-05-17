<?php

namespace Domain\Auth\Http\Resources;

use Domain\Auth\Models\Role;
use Domain\Support\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/** @property-read Role $resource */
abstract class RoleResource extends BaseResource
{
    public function toArray(Request $request)
    {
        return $this->withAttributes([
            'name',
            'guard_name',
        ]);
    }
}
