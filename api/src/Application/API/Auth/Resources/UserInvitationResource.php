<?php

namespace Application\API\Auth\Resources;

use Domain\Auth\Models\UserInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read UserInvitation $resource */
class UserInvitationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'email' => $this->resource->email,
            'status' => $this->resource->status?->value,
            'expires_at' => $this->resource->expires_at,
            'accepted_at' => $this->resource->accepted_at,
            'revoked_at' => $this->resource->revoked_at,
            'resent_at' => $this->resource->resent_at,
            'role_id' => $this->resource->role_id,
            'employee_id' => $this->resource->employee_id,
            'invited_user_id' => $this->resource->invited_user_id,
        ];
    }
}
