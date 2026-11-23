<?php

namespace Domain\Employees\Http\Resources;

use Domain\Employees\Models\OrganizationLevel;
use Domain\Support\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/** @property-read OrganizationLevel $resource */
abstract class OrganizationLevelResource extends BaseResource
{
    public function toArray(Request $request)
    {
        return $this->withAttributes([
            'name',
            'sort_order',
            'color',
            'is_active',
        ]);
    }
}
