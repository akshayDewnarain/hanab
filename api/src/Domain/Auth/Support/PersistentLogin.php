<?php

namespace Domain\Auth\Support;

use Domain\Auth\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Keeps a completed sign-in for 30 days when the person asked to be remembered.
 * The cookie is issued only after the authenticator (or recovery code) succeeds.
 */
final class PersistentLogin
{
    public const DAYS = 30;

    public static function minutes(): int
    {
        return self::DAYS * 24 * 60;
    }

    public function establish(User $user, bool $remember): void
    {
        $guard = Auth::guard('web');

        if ($remember) {
            config([
                'session.lifetime' => self::minutes(),
                'session.expire_on_close' => false,
            ]);
            $guard->setRememberDuration(self::minutes());
            $guard->login($user, true);

            return;
        }

        $guard->login($user, false);
        $guard->getCookieJar()->queue(
            $guard->getCookieJar()->forget($guard->getRecallerName())
        );
    }
}
