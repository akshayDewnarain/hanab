<?php

namespace Domain\Auth\Services;

use Illuminate\Support\Str;

final class PermissionService
{
    /**
     * Resource fragment used in permission names (e.g. `employees`, `users`).
     */
    public static function resourceKey(string $modelClass): string
    {
        return Str::snake(Str::pluralStudly(class_basename($modelClass)));
    }

    public static function viewPermission(string $modelClass): string
    {
        return 'view '.self::resourceKey($modelClass);
    }

    public static function createPermission(string $modelClass): string
    {
        return 'create '.self::resourceKey($modelClass);
    }

    public static function updatePermission(string $modelClass): string
    {
        return 'update '.self::resourceKey($modelClass);
    }

    public static function deletePermission(string $modelClass): string
    {
        return 'delete '.self::resourceKey($modelClass);
    }

    /**
     * @return array<int, string>
     */
    public static function crudPermissions(string $modelClass): array
    {
        return [
            self::viewPermission($modelClass),
            self::createPermission($modelClass),
            self::updatePermission($modelClass),
            self::deletePermission($modelClass),
        ];
    }
}
