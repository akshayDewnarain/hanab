<?php

namespace Domain\Employees\Support;

use Domain\Employees\Models\OrganizationLevel;

/**
 * A level is a color and a label. It does not decide who reports to whom.
 */
final class OrganizationLevelStyle
{
    public static function effective(?OrganizationLevel $employeeLevel, ?OrganizationLevel $roleLevel): ?OrganizationLevel
    {
        foreach ([$employeeLevel, $roleLevel] as $level) {
            if ($level !== null && $level->is_active) {
                return $level;
            }
        }

        return null;
    }

    /**
     * @return array{id: int, name: string, color: string}|null
     */
    public static function payload(?OrganizationLevel $level): ?array
    {
        if ($level === null) {
            return null;
        }

        return [
            'id' => $level->id,
            'name' => $level->name,
            'color' => $level->color,
        ];
    }
}
