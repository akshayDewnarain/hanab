<?php

namespace Domain\Employees\Support;

use Domain\Employees\Models\Employee;

/**
 * Reporting lines run from one employee to another.
 * A role parent is a separate classification and is not used here.
 */
final class EmployeeReporting
{
    public static function createsCycle(Employee $employee, int $managerId): bool
    {
        if ($managerId === $employee->id) {
            return true;
        }

        $current = $managerId;
        $seen = [];

        for ($step = 0; $step < 50 && $current > 0; $step++) {
            if ($current === $employee->id || isset($seen[$current])) {
                return true;
            }

            $seen[$current] = true;
            $next = Employee::query()->whereKey($current)->value('manager_id');
            $current = $next === null ? 0 : (int) $next;
        }

        return false;
    }
}
