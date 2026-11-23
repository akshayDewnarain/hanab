<?php

namespace Domain\Auth\Support;

use Domain\Auth\Enums\UserStatus;
use Domain\Auth\Models\User;

final class SuperAdminGuard
{
    public static function isLastActive(User $user): bool
    {
        if (! $user->hasRole(BootstrapRoleNames::SUPER_ADMIN)) {
            return false;
        }

        if ($user->status !== UserStatus::Active || ! $user->hasPassword()) {
            return false;
        }

        return ! User::query()
            ->clientVisible()
            ->whereKeyNot($user->id)
            ->where('status', UserStatus::Active->value)
            ->whereNotNull('password_set_at')
            ->whereHas('roles', fn ($query) => $query->where('name', BootstrapRoleNames::SUPER_ADMIN))
            ->exists();
    }

    public static function activeExists(): bool
    {
        return User::query()
            ->clientVisible()
            ->where('status', UserStatus::Active->value)
            ->whereNotNull('password_set_at')
            ->whereHas('roles', fn ($query) => $query->where('name', BootstrapRoleNames::SUPER_ADMIN))
            ->exists();
    }
}
