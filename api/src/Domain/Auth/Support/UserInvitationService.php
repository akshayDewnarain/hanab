<?php

namespace Domain\Auth\Support;

use Domain\Auth\Enums\UserInvitationStatus;
use Domain\Auth\Enums\UserStatus;
use Domain\Auth\Exceptions\InvitationDeliveryException;
use Domain\Auth\Mail\UserInvitationMail;
use Domain\Auth\Models\Role;
use Domain\Auth\Models\User;
use Domain\Auth\Models\UserInvitation;
use Domain\Employees\Models\Employee;
use Domain\Support\Mail\Mailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class UserInvitationService
{
    public function __construct(
        private readonly Mailer $mailer,
    ) {}

    public function invite(
        ?User $inviter,
        string $email,
        ?string $name = null,
        ?int $roleId = null,
        ?int $employeeId = null,
        bool $allowSuperAdminRole = false,
    ): UserInvitation {
        $email = $this->normalizeEmail($email);
        $existing = User::query()->where('email', $email)->first();
        if ($existing?->isInternalAccount()) {
            throw new InvalidArgumentException('This email cannot be invited.');
        }
        $this->assertRoleAllowed($roleId, $allowSuperAdminRole);
        $this->assertNoLiveInvitation($email);

        $rawToken = Str::random(64);

        $invitation = DB::transaction(function () use ($inviter, $email, $name, $roleId, $employeeId, $rawToken): UserInvitation {
            $user = User::query()->where('email', $email)->first();

            if ($user?->isInternalAccount()) {
                throw new InvalidArgumentException('This email cannot be invited.');
            }

            if ($user?->hasPassword()) {
                throw new InvalidArgumentException('This account already has a password.');
            }

            if ($user === null) {
                $user = User::query()->create([
                    'name' => $name ?: Str::before($email, '@'),
                    'email' => $email,
                    'password' => null,
                    'status' => UserStatus::Invited,
                    'invited_at' => now(),
                ]);
            } else {
                $user->update([
                    'name' => $name ?: $user->name,
                    'status' => UserStatus::Invited,
                    'invited_at' => $user->invited_at ?? now(),
                ]);
            }

            if ($roleId !== null) {
                $user->syncRoles([Role::query()->findOrFail($roleId)]);
            }

            $this->linkEmployee($user, $employeeId);

            $invitation = $this->storeInvitation(
                user: $user->fresh(),
                inviter: $inviter,
                roleId: $roleId,
                employeeId: $employeeId ?? $user->employee_id,
                rawToken: $rawToken,
                status: UserInvitationStatus::Pending,
            );

            return $invitation;
        });

        $this->deliver($invitation, $rawToken);

        return $invitation->fresh(['invitedUser', 'role', 'employee']);
    }

    public function resend(UserInvitation $invitation, ?User $actor = null): UserInvitation
    {
        if (! $invitation->isPending()) {
            throw new InvalidArgumentException('Only a pending invitation can be sent again.');
        }

        if ($invitation->isExpired()) {
            $this->releaseLiveKey($invitation, UserInvitationStatus::Expired);

            throw new InvalidArgumentException('This invitation has expired.');
        }

        $user = $invitation->invitedUser;
        if ($user?->hasPassword()) {
            throw new InvalidArgumentException('This account already has a password.');
        }

        $rawToken = Str::random(64);

        $invitation = DB::transaction(function () use ($invitation, $rawToken): UserInvitation {
            $invitation->update([
                'token_hash' => hash('sha256', $rawToken),
                'status' => UserInvitationStatus::Resent,
                'resent_at' => now(),
                'expires_at' => $this->expiry(),
                'live_email' => $invitation->email,
            ]);

            return $invitation->fresh(['invitedUser']);
        });

        $this->deliver($invitation, $rawToken, $actor);

        return $invitation->fresh(['invitedUser', 'role', 'employee']);
    }

    public function resendForUser(User $user, ?User $actor = null): UserInvitation
    {
        if ($user->hasPassword()) {
            throw new InvalidArgumentException('This account already has a password.');
        }

        $invitation = $this->liveInvitationFor($user);

        if ($invitation === null && $user->isInternalAccount()) {
            return $this->reissueInternalInvitation($user);
        }

        if ($invitation === null) {
            return $this->invite(
                inviter: $actor,
                email: $user->email,
                name: $user->name,
                roleId: $user->roles()->first()?->id,
                employeeId: $user->employee_id,
                allowSuperAdminRole: $actor?->hasRole(BootstrapRoleNames::SUPER_ADMIN) === true
                    || $user->hasRole(BootstrapRoleNames::SUPER_ADMIN),
            );
        }

        return $this->resend($invitation, $actor);
    }

    public function provisionInternal(string $email, string $name, ?int $roleId = null): UserInvitation
    {
        $email = $this->normalizeEmail($email);
        $this->assertRoleAllowed($roleId, true);

        if (User::query()->where('email', $email)->exists()) {
            throw new InvalidArgumentException('An account already uses that email.');
        }

        $rawToken = Str::random(64);
        $invitation = DB::transaction(function () use ($email, $name, $roleId, $rawToken): UserInvitation {
            $user = new User;
            $user->forceFill([
                'name' => $name !== '' ? $name : Str::before($email, '@'),
                'email' => $email,
                'password' => null,
                'status' => UserStatus::Invited,
                'invited_at' => now(),
                'is_internal_account' => true,
                'employee_id' => null,
            ])->save();

            if ($roleId !== null) {
                $user->syncRoles([Role::query()->findOrFail($roleId)]);
            }

            return $this->storeInvitation(
                user: $user->fresh(),
                inviter: null,
                roleId: $roleId,
                employeeId: null,
                rawToken: $rawToken,
                status: UserInvitationStatus::Pending,
            );
        });

        $this->deliver($invitation, $rawToken);

        return $invitation->fresh(['invitedUser', 'role']);
    }

    private function reissueInternalInvitation(User $user): UserInvitation
    {
        $rawToken = Str::random(64);
        $roleId = $user->roles()->first()?->id;
        $invitation = DB::transaction(function () use ($user, $rawToken, $roleId): UserInvitation {
            return $this->storeInvitation(
                user: $user,
                inviter: null,
                roleId: $roleId,
                employeeId: null,
                rawToken: $rawToken,
                status: UserInvitationStatus::Pending,
            );
        });

        $this->deliver($invitation, $rawToken);

        return $invitation->fresh(['invitedUser', 'role']);
    }

    public function revoke(UserInvitation $invitation): UserInvitation
    {
        if ($invitation->status === UserInvitationStatus::Accepted) {
            throw new InvalidArgumentException('An accepted invitation cannot be revoked.');
        }

        $this->releaseLiveKey($invitation, UserInvitationStatus::Revoked);

        return $invitation->fresh();
    }

    public function revokeForUser(User $user): UserInvitation
    {
        $invitation = $this->liveInvitationFor($user);

        if ($invitation === null) {
            throw new InvalidArgumentException('There is no pending invitation for this account.');
        }

        return $this->revoke($invitation);
    }

    /**
     * @return array{state: string, email?: string, expires_at?: string, name?: string|null}
     */
    public function preview(string $rawToken): array
    {
        $invitation = $this->findByRawToken($rawToken);

        if ($invitation === null) {
            return ['state' => 'invalid'];
        }

        if ($invitation->status === UserInvitationStatus::Accepted) {
            return ['state' => 'used', 'email' => $invitation->email];
        }

        if ($invitation->status === UserInvitationStatus::Revoked || $invitation->revoked_at !== null) {
            return ['state' => 'revoked', 'email' => $invitation->email];
        }

        if ($invitation->isExpired()) {
            if ($invitation->isPending()) {
                $this->releaseLiveKey($invitation, UserInvitationStatus::Expired);
            }

            return ['state' => 'expired', 'email' => $invitation->email];
        }

        if (! $invitation->isAcceptable()) {
            return ['state' => 'invalid'];
        }

        return [
            'state' => 'valid',
            'email' => $invitation->email,
            'expires_at' => $invitation->expires_at?->toIso8601String(),
            'name' => $invitation->invitedUser?->name,
        ];
    }

    public function accept(string $rawToken, string $password, ?string $name, ?string $ip, ?string $userAgent): UserInvitation
    {
        $tokenHash = hash('sha256', $rawToken);

        return DB::transaction(function () use ($tokenHash, $password, $name, $ip, $userAgent): UserInvitation {
            $invitation = UserInvitation::query()
                ->where('token_hash', $tokenHash)
                ->lockForUpdate()
                ->first();

            if ($invitation === null || ! $invitation->isAcceptable()) {
                throw new InvalidArgumentException('This invitation is no longer valid.');
            }

            $user = User::query()->whereKey($invitation->invited_user_id)->lockForUpdate()->first();

            if ($user === null) {
                throw new InvalidArgumentException('This invitation is no longer valid.');
            }

            $user->update([
                'name' => $name ?: $user->name,
                'password' => $password,
                'password_set_at' => now(),
                'email_verified_at' => now(),
                'status' => UserStatus::Active,
            ]);

            if ($invitation->employee_id !== null && $user->employee_id === null) {
                $this->linkEmployee($user, $invitation->employee_id);
            }

            $invitation->update([
                'status' => UserInvitationStatus::Accepted,
                'accepted_at' => now(),
                'accepted_ip' => $ip,
                'accepted_user_agent' => $userAgent ? Str::limit($userAgent, 255, '') : null,
                'live_email' => null,
            ]);

            UserInvitation::query()
                ->where('invited_user_id', $user->id)
                ->whereKeyNot($invitation->id)
                ->whereIn('status', [UserInvitationStatus::Pending->value, UserInvitationStatus::Resent->value])
                ->update([
                    'status' => UserInvitationStatus::Revoked->value,
                    'revoked_at' => now(),
                    'live_email' => null,
                ]);

            return $invitation->fresh(['invitedUser']);
        });
    }

    public function buildAcceptUrl(string $rawToken): string
    {
        $base = rtrim((string) config('invitation.frontend_url'), '/');
        $path = trim((string) config('invitation.accept_path', '/invitations/accept'), '/');

        return "{$base}/{$path}/{$rawToken}";
    }

    private function storeInvitation(
        User $user,
        ?User $inviter,
        ?int $roleId,
        ?int $employeeId,
        string $rawToken,
        UserInvitationStatus $status,
    ): UserInvitation {
        return UserInvitation::query()->create([
            'email' => $user->email,
            'invited_user_id' => $user->id,
            'employee_id' => $employeeId,
            'invited_by_user_id' => $inviter?->id,
            'role_id' => $roleId,
            'status' => $status,
            'token_hash' => hash('sha256', $rawToken),
            'live_email' => $user->email,
            'expires_at' => $this->expiry(),
            'required_actions' => ['setup_password'],
        ]);
    }

    private function deliver(UserInvitation $invitation, string $rawToken, ?User $actor = null): void
    {
        try {
            $this->mailer->sendTo($invitation->email, new UserInvitationMail(
                acceptUrl: $this->buildAcceptUrl($rawToken),
                recipientEmail: $invitation->email,
                expiresInDays: (int) config('invitation.expires_days', 7),
            ));
        } catch (\Throwable $exception) {
            report($exception);

            throw new InvitationDeliveryException('The invitation email could not be sent.');
        }

        $invitation->invitedUser?->update(['last_invitation_sent_at' => now()]);

        try {
            activity('auth')
                ->causedBy($actor)
                ->performedOn($invitation->invitedUser)
                ->event('user_invitation_sent')
                ->withProperties([
                    'invitation_id' => $invitation->id,
                    'email' => $invitation->email,
                ])
                ->log('Account invitation sent');
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function findByRawToken(string $rawToken): ?UserInvitation
    {
        return UserInvitation::query()
            ->where('token_hash', hash('sha256', $rawToken))
            ->with('invitedUser')
            ->first();
    }

    private function liveInvitationFor(User $user): ?UserInvitation
    {
        return UserInvitation::query()
            ->where('invited_user_id', $user->id)
            ->whereIn('status', [UserInvitationStatus::Pending->value, UserInvitationStatus::Resent->value])
            ->whereNotNull('live_email')
            ->latest('id')
            ->first();
    }

    private function releaseLiveKey(UserInvitation $invitation, UserInvitationStatus $status): void
    {
        $invitation->update([
            'status' => $status,
            'revoked_at' => $status === UserInvitationStatus::Revoked ? now() : $invitation->revoked_at,
            'live_email' => null,
        ]);
    }

    private function assertNoLiveInvitation(string $email): void
    {
        $exists = UserInvitation::query()
            ->where('live_email', $email)
            ->exists();

        if ($exists) {
            throw new InvalidArgumentException('A pending invitation already exists for this email.');
        }
    }

    private function assertRoleAllowed(?int $roleId, bool $allowSuperAdminRole): void
    {
        if ($roleId === null) {
            return;
        }

        $role = Role::query()->find($roleId);

        if ($role === null) {
            throw new InvalidArgumentException('The selected role does not exist.');
        }

        if ($role->name === BootstrapRoleNames::SUPER_ADMIN && ! $allowSuperAdminRole) {
            throw new InvalidArgumentException('That role cannot be assigned from this account.');
        }
    }

    private function linkEmployee(User $user, ?int $employeeId): void
    {
        if ($employeeId === null) {
            return;
        }

        $employee = Employee::query()->find($employeeId);

        if ($employee === null) {
            throw new InvalidArgumentException('The selected colleague does not exist.');
        }

        $taken = User::query()
            ->where('employee_id', $employee->id)
            ->whereKeyNot($user->id)
            ->exists();

        if ($taken) {
            throw new InvalidArgumentException('This colleague is already linked to a login.');
        }

        $user->update(['employee_id' => $employee->id]);
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function expiry(): \Illuminate\Support\Carbon
    {
        return now()->addDays((int) config('invitation.expires_days', 7));
    }
}
