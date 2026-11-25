<?php

namespace Domain\Auth\Support;

/**
 * Help Center and Knowledge Base permissions are assigned only by a super-admin.
 * Client administrators must not grant, change, or revoke them.
 */
final class ReservedContentPermissions
{
    public static function isReserved(string $name): bool
    {
        return (bool) preg_match('/^(?:view|create|update|delete|publish|manage|restore) (?:help|knowledge) /', $name);
    }

    /**
     * @param  array<int, string>  $names
     * @return array<int, string>
     */
    public static function only(array $names): array
    {
        return array_values(array_filter($names, self::isReserved(...)));
    }

    /**
     * @param  array<int, string>  $names
     * @return array<int, string>
     */
    public static function except(array $names): array
    {
        return array_values(array_filter(
            $names,
            static fn (string $name): bool => ! self::isReserved($name),
        ));
    }
}
