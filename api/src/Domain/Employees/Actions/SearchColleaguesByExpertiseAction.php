<?php

namespace Domain\Employees\Actions;

use Domain\Employees\DataTransferObjects\ExpertiseSearchResultData;
use Domain\Employees\Models\Employee;
use Domain\Employees\Support\ExpertiseExplanation;
use Domain\Employees\Support\ExpertiseTerms;
use Domain\Support\Actions\BaseAction;

class SearchColleaguesByExpertiseAction extends BaseAction
{
    /**
     * Active colleagues matching a name, email, role, location, or expertise term.
     * Team is not part of this result. The team field is not in the product yet.
     *
     * @return list<ExpertiseSearchResultData>
     */
    public function execute(string $query): array
    {
        if (ExpertiseTerms::needle($query) === '') {
            return [];
        }

        $employees = Employee::query()
            ->matchingColleague($query)
            ->with([
                'employeeRole',
                'employeeLocation',
                'skills' => fn ($skillQuery) => $skillQuery->where('skills.is_active', true),
                'certificates' => fn ($certificateQuery) => $certificateQuery->where('certificates.is_active', true),
            ])
            ->get()
            ->sortBy(function (Employee $employee) use ($query): array {
                return [
                    -ExpertiseExplanation::bestScore($employee, $query),
                    mb_strtolower((string) $employee->last_name),
                    mb_strtolower((string) $employee->first_name),
                ];
            })
            ->values();

        $results = [];
        foreach ($employees as $employee) {
            $matches = ExpertiseExplanation::forEmployee($employee, $query);
            if ($matches === [] && ExpertiseExplanation::reason($employee, $query) === null) {
                continue;
            }

            $results[] = ExpertiseSearchResultData::from([
                'id' => $employee->id,
                'name' => trim($employee->first_name.' '.$employee->last_name),
                'role' => $employee->employeeRole?->name,
                'location' => $employee->employeeLocation?->name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'matches' => array_map(
                    fn (array $match): array => [
                        'kind' => $match['kind'],
                        'name' => $match['name'],
                        'code' => $match['code'],
                        'matched_on' => $match['matched_on'],
                        'synonym' => $match['synonym'],
                    ],
                    $matches,
                ),
            ]);
        }

        return $results;
    }
}
