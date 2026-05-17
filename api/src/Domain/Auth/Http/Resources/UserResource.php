<?php

namespace Domain\Auth\Http\Resources;

use Domain\Auth\Models\User;
use Domain\Support\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/** @property-read User $resource */
abstract class UserResource extends BaseResource
{
    public function toArray(Request $request)
    {
        return $this->withAttributes([
            'name',
            'email',
            'email_verified_at',
        ]);
    }
}
