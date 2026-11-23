<?php

namespace Domain\Auth\Models;

use Domain\Auth\Enums\UserInvitationStatus;
use Domain\Employees\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInvitation extends Model
{
    protected $table = 'user_invitations';

    protected $fillable = [
        'email',
        'invited_user_id',
        'employee_id',
        'invited_by_user_id',
        'role_id',
        'status',
        'token_hash',
        'live_email',
        'expires_at',
        'accepted_at',
        'revoked_at',
        'resent_at',
        'accepted_ip',
        'accepted_user_agent',
        'required_actions',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'invited_user_id' => 'integer',
            'employee_id' => 'integer',
            'invited_by_user_id' => 'integer',
            'role_id' => 'integer',
            'status' => UserInvitationStatus::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'resent_at' => 'datetime',
            'required_actions' => 'array',
        ];
    }

    public function invitedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isPending(): bool
    {
        return in_array($this->status, [UserInvitationStatus::Pending, UserInvitationStatus::Resent], true);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast() || $this->status === UserInvitationStatus::Expired;
    }

    public function isAcceptable(): bool
    {
        return $this->isPending()
            && ! $this->isExpired()
            && $this->revoked_at === null
            && $this->accepted_at === null;
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        return $this->resolveRouteBindingQuery(
            $this->newQuery()->whereHas('invitedUser', fn ($query) => $query->clientVisible()),
            $value,
            $field,
        )->first();
    }
}
