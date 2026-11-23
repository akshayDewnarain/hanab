<?php

namespace Domain\Auth\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Composition rules used by invitation acceptance, reset, and password changes.
 * Breached-password screening stays off: it calls an external service and is a
 * privacy and availability choice for a later project. Length-first policies can
 * replace this rule without changing the callers.
 */
final class PasswordPolicy
{
    public const MIN = 12;

    public const MAX = 128;

    public const CHECK_BREACHED = false;

    public static function rule(): Password
    {
        $rule = Password::min(self::MIN)
            ->max(self::MAX)
            ->mixedCase()
            ->numbers()
            ->symbols();

        if (self::CHECK_BREACHED) {
            $rule = $rule->uncompromised();
        }

        return $rule;
    }

    /**
     * @return array<string, int|bool>
     */
    public static function descriptor(): array
    {
        return [
            'min' => self::MIN,
            'max' => self::MAX,
            'uppercase' => true,
            'lowercase' => true,
            'numbers' => true,
            'symbols' => true,
            'breached' => self::CHECK_BREACHED,
        ];
    }
}
