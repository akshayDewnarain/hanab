<?php

namespace Domain\Employees\Support;

use Domain\Employees\Models\EmployeeRole;

final class EmployeeRoleTree
{
    public static function createsCycle(EmployeeRole $role, int $parentId): bool
    {
        $current = $parentId;
        $seen = [];

        for ($step = 0; $step < 50 && $current > 0; $step++) {
            if ($current === $role->id || isset($seen[$current])) {
                return true;
            }

            $seen[$current] = true;
            $next = EmployeeRole::query()->whereKey($current)->value('parent_id');
            $current = $next === null ? 0 : (int) $next;
        }

        return false;
    }
}
