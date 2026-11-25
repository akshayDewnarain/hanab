<?php

namespace Domain\Employees\Actions;

use Domain\Employees\Models\Employee;
use Domain\Employees\Models\EmployeeLocation;
use Domain\Support\Actions\BaseAction;
use Illuminate\Database\Eloquent\Builder;

class SummarizePeopleOverviewAction extends BaseAction
{
    /**
     * Aggregate counts for active employees and active work locations.
     * Skill and certificate figures come from the pivot tables, one employee at a time.
     *
     * @return array{
     *     active_employees: int,
     *     without_skills: int,
     *     without_certificates: int,
     *     without_location: int,
     *     without_email: int,
     *     without_phone: int,
     *     with_skills: int,
     *     with_certificates: int,
     *     with_location: int,
     *     with_email: int,
     *     with_phone: int,
     *     skills_percent: int,
     *     certificates_percent: int,
     *     location_percent: int,
     *     email_percent: int,
     *     phone_percent: int,
     *     locations: int,
     *     locations_without_coordinates: int,
     *     work_locations: list<array{id: int, name: string, latitude: float, longitude: float, employee_count: int}>
     * }
     */
    public function execute(): array
    {
        $active = Employee::query()->where('is_active', true);
        $total = (clone $active)->count();
        $withoutSkills = (clone $active)->whereDoesntHave('skills')->count();
        $withoutCertificates = (clone $active)->whereDoesntHave('certificates')->count();
        $withoutLocation = (clone $active)->whereNull('employee_location_id')->count();
        $withoutEmail = (clone $active)->where(fn (Builder $query) => $query->whereNull('business_email')->orWhere('business_email', ''))->count();
        $withoutPhone = (clone $active)->where(fn (Builder $query) => $query->whereNull('business_phone')->orWhere('business_phone', ''))->count();

        $locations = EmployeeLocation::query()->where('is_active', true);
        $locationTotal = (clone $locations)->count();
        $unmapped = (clone $locations)
            ->where(fn (Builder $query) => $query->whereNull('latitude')->orWhereNull('longitude'))
            ->count();

        $workLocations = EmployeeLocation::query()
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->withCount(['employees as employee_count' => fn (Builder $query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get();

        return [
            'active_employees' => $total,
            'without_skills' => $withoutSkills,
            'without_certificates' => $withoutCertificates,
            'without_location' => $withoutLocation,
            'without_email' => $withoutEmail,
            'without_phone' => $withoutPhone,
            'with_skills' => $total - $withoutSkills,
            'with_certificates' => $total - $withoutCertificates,
            'with_location' => $total - $withoutLocation,
            'with_email' => $total - $withoutEmail,
            'with_phone' => $total - $withoutPhone,
            'skills_percent' => $this->percent($total - $withoutSkills, $total),
            'certificates_percent' => $this->percent($total - $withoutCertificates, $total),
            'location_percent' => $this->percent($total - $withoutLocation, $total),
            'email_percent' => $this->percent($total - $withoutEmail, $total),
            'phone_percent' => $this->percent($total - $withoutPhone, $total),
            'locations' => $locationTotal,
            'locations_without_coordinates' => $unmapped,
            'work_locations' => $workLocations
                ->map(fn (EmployeeLocation $location): array => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'latitude' => (float) $location->latitude,
                    'longitude' => (float) $location->longitude,
                    'employee_count' => (int) $location->employee_count,
                ])
                ->values()
                ->all(),
        ];
    }

    private function percent(int $filled, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return (int) round(($filled / $total) * 100);
    }
}
