<?php

namespace Domain\Employees\Http\Resources;

use Domain\Employees\DataTransferObjects\ExpertiseSearchResultData;
use Domain\Support\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/** @property-read ExpertiseSearchResultData $resource */
abstract class ExpertiseSearchResultResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'role' => $this->resource->role,
            'location' => $this->resource->location,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'matches' => $this->resource->matches,
        ];
    }
}
