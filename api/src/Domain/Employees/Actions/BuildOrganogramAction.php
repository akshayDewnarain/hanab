<?php

namespace Domain\Employees\Actions;

use Domain\Auth\Models\User;
use Domain\Auth\Services\PermissionService;
use Domain\Auth\Support\Maintainer;
use Domain\Employees\Models\Employee;
use Domain\Employees\Models\EmployeeRole;
use Domain\Employees\Models\OrganizationLevel;
use Domain\Employees\Support\OrganizationLevelStyle;
use Domain\Support\Actions\BaseAction;
use Illuminate\Validation\ValidationException;

class BuildOrganogramAction extends BaseAction
{
    /**
     * Management edges follow employee manager_id.
     * Role edges follow the role parent. A role parent is not a manager.
     *
     * @return array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>}
     */
    public function execute(string $view, ?int $locationId, ?string $levelFilter, ?User $viewer): array
    {
        return $view === 'roles'
            ? $this->roles($locationId, $levelFilter)
            : $this->management($locationId, $levelFilter, $viewer);
    }

    /**
     * @return array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>}
     */
    private function management(?int $locationId, ?string $levelFilter, ?User $viewer): array
    {
        $levelId = $this->levelId($levelFilter);
        $showNumber = $viewer instanceof User && Maintainer::allows($viewer, PermissionService::updatePermission(Employee::class));

        $employees = Employee::query()
            ->where('is_active', true)
            ->with([
                'employeeRole.organizationLevel',
                'employeeLocation',
                'organizationLevel',
                'manager',
                'media',
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->keyBy(fn (Employee $employee): int => (int) $employee->id);

        $reportCounts = [];
        foreach ($employees as $employee) {
            $managerId = $employee->manager_id === null ? null : (int) $employee->manager_id;
            if ($managerId !== null && $employees->has($managerId)) {
                $reportCounts[$managerId] = ($reportCounts[$managerId] ?? 0) + 1;
            }
        }

        $primary = $employees->filter(
            fn (Employee $employee): bool => $this->matches($employee, $locationId, $levelFilter, $levelId),
        );

        $included = $primary->keyBy(fn (Employee $employee): int => (int) $employee->id);
        foreach ($primary as $employee) {
            $managerId = $employee->manager_id === null ? null : (int) $employee->manager_id;
            if ($managerId === null || ! $employees->has($managerId) || $included->has($managerId)) {
                continue;
            }
            $included->put($managerId, $employees->get($managerId));
        }

        $nodes = [];
        $edges = [];
        foreach ($included->sortBy(fn (Employee $employee): string => $employee->last_name.' '.$employee->first_name) as $employee) {
            $id = (int) $employee->id;
            $managerId = $employee->manager_id === null ? null : (int) $employee->manager_id;
            $manager = $managerId === null ? null : $employee->manager;
            $note = null;
            $managerName = null;

            if ($managerId !== null && ($manager === null || ! $manager->is_active)) {
                $note = 'inactive';
                $managerName = $manager === null ? null : trim($manager->first_name.' '.$manager->last_name);
            } elseif ($managerId !== null && ! $included->has($managerId)) {
                $note = 'missing';
            }

            $level = OrganizationLevelStyle::effective($employee->organizationLevel, $employee->employeeRole?->organizationLevel);
            $media = $employee->getFirstMedia('image');
            $node = [
                'id' => 'employee-'.$id,
                'type' => 'employee',
                'employeeId' => $id,
                'name' => trim($employee->first_name.' '.$employee->last_name),
                'initials' => $this->initials($employee),
                'photoUrl' => $media?->getUrl('thumb'),
                'role' => $employee->employeeRole?->name,
                'roleId' => $employee->employee_role_id,
                'location' => $employee->employeeLocation?->name,
                'locationId' => $employee->employee_location_id,
                'level' => OrganizationLevelStyle::payload($level),
                'directReportsCount' => $reportCounts[$id] ?? 0,
                'context' => ! $primary->has($id),
                'managerNote' => $note,
                'managerName' => $managerName,
            ];
            if ($showNumber) {
                $node['employeeNumber'] = $employee->employee_number;
            }
            $nodes[] = $node;

            if ($managerId !== null && $included->has($managerId) && $employees->has($managerId)) {
                $edges[] = [
                    'id' => 'report-'.$managerId.'-'.$id,
                    'source' => 'employee-'.$managerId,
                    'target' => 'employee-'.$id,
                    'type' => 'reports_to',
                ];
            }
        }

        usort($edges, fn (array $left, array $right): int => $left['id'] <=> $right['id']);

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    /**
     * @return array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>}
     */
    private function roles(?int $locationId, ?string $levelFilter): array
    {
        $levelId = $this->levelId($levelFilter);
        $roles = EmployeeRole::query()
            ->where('is_active', true)
            ->with([
                'organizationLevel',
                'employees' => function ($query) use ($locationId): void {
                    $query->where('is_active', true)
                        ->with('employeeLocation')
                        ->orderBy('last_name')
                        ->orderBy('first_name');
                    if ($locationId !== null) {
                        $query->where('employee_location_id', $locationId);
                    }
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $byId = $roles->keyBy(fn (EmployeeRole $role): int => (int) $role->id);
        $primaryIds = $roles
            ->filter(function (EmployeeRole $role) use ($levelFilter, $levelId): bool {
                if ($levelFilter === null) {
                    return true;
                }
                $level = $role->organizationLevel !== null && $role->organizationLevel->is_active
                    ? $role->organizationLevel
                    : null;
                if ($levelFilter === 'none') {
                    return $level === null;
                }

                return $level !== null && (int) $level->id === $levelId;
            })
            ->map(fn (EmployeeRole $role): int => (int) $role->id)
            ->all();

        $included = [];
        foreach ($primaryIds as $roleId) {
            $included[$roleId] = false;
            $parentId = $byId->get($roleId)?->parent_id;
            $parentId = $parentId === null ? null : (int) $parentId;
            if ($parentId !== null && $byId->has($parentId) && ! in_array($parentId, $primaryIds, true)) {
                $included[$parentId] = true;
            }
        }

        $nodes = [];
        $edges = [];
        foreach ($roles as $role) {
            $id = (int) $role->id;
            if (! array_key_exists($id, $included)) {
                continue;
            }

            $people = $role->employees->map(function (Employee $employee): array {
                return [
                    'id' => $employee->id,
                    'name' => trim($employee->first_name.' '.$employee->last_name),
                    'location' => $employee->employeeLocation?->name,
                ];
            })->values()->all();

            $nodes[] = [
                'id' => 'role-'.$id,
                'type' => 'role',
                'roleId' => $id,
                'name' => $role->name,
                'description' => $role->description,
                'level' => OrganizationLevelStyle::payload(
                    $role->organizationLevel !== null && $role->organizationLevel->is_active ? $role->organizationLevel : null,
                ),
                'people' => $people,
                'directReportsCount' => $role->employees->count(),
                'context' => $included[$id] === true,
            ];

            $parentId = $role->parent_id === null ? null : (int) $role->parent_id;
            if ($parentId !== null && array_key_exists($parentId, $included)) {
                $edges[] = [
                    'id' => 'role-'.$parentId.'-'.$id,
                    'source' => 'role-'.$parentId,
                    'target' => 'role-'.$id,
                    'type' => 'role_parent',
                ];
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    private function matches(Employee $employee, ?int $locationId, ?string $levelFilter, ?int $levelId): bool
    {
        if ($locationId !== null && (int) $employee->employee_location_id !== $locationId) {
            return false;
        }

        if ($levelFilter === null) {
            return true;
        }

        $level = OrganizationLevelStyle::effective($employee->organizationLevel, $employee->employeeRole?->organizationLevel);
        if ($levelFilter === 'none') {
            return $level === null;
        }

        return $level !== null && (int) $level->id === $levelId;
    }

    private function levelId(?string $levelFilter): ?int
    {
        if ($levelFilter === null || $levelFilter === '' || $levelFilter === 'none') {
            return null;
        }

        if (! ctype_digit($levelFilter) || ! OrganizationLevel::query()->whereKey((int) $levelFilter)->exists()) {
            throw ValidationException::withMessages([
                'organization_level_id' => 'Choose an organization level.',
            ]);
        }

        return (int) $levelFilter;
    }

    private function initials(Employee $employee): string
    {
        $stored = trim((string) $employee->initials);
        if ($stored !== '') {
            return $stored;
        }

        $letters = '';
        foreach ([$employee->first_name, $employee->last_name] as $part) {
            $part = trim((string) $part);
            if ($part !== '') {
                $letters .= mb_substr($part, 0, 1);
            }
        }

        return mb_strtoupper($letters);
    }
}
