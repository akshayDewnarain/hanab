<?php

namespace Domain\Employees\Support;

use Domain\Employees\Models\Certificate;
use Domain\Employees\Models\Employee;
use Domain\Employees\Models\Skill;

/**
 * Why a colleague matches a search, using stored skills, certificates, and identity fields.
 */
final class ExpertiseExplanation
{
    /**
     * @return list<array{kind: string, name: string, code: ?string, matched_on: string, synonym: ?string, score: int}>
     */
    public static function forEmployee(Employee $employee, string $query): array
    {
        if (ExpertiseTerms::needle($query) === '') {
            return [];
        }

        $matches = [];

        foreach ($employee->skills as $skill) {
            if (! $skill instanceof Skill) {
                continue;
            }

            $match = self::catalog('skill', $skill, $query);
            if ($match !== null) {
                $matches[] = $match;
            }
        }

        foreach ($employee->certificates as $certificate) {
            if (! $certificate instanceof Certificate) {
                continue;
            }

            $match = self::catalog('certificate', $certificate, $query);
            if ($match !== null) {
                $matches[] = $match;
            }
        }

        usort($matches, fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        return $matches;
    }

    public static function bestScore(Employee $employee, string $query): int
    {
        $matches = self::forEmployee($employee, $query);
        if ($matches !== []) {
            return $matches[0]['score'];
        }

        return self::reason($employee, $query) === null ? 0 : 20;
    }

    /**
     * Set when the colleague matched without a skill or certificate hit.
     */
    public static function reason(Employee $employee, string $query): ?string
    {
        if (self::forEmployee($employee, $query) !== []) {
            return 'expertise';
        }

        $name = trim($employee->first_name.' '.$employee->last_name);
        if (ExpertiseTerms::contains($name, $query)) {
            return 'name';
        }

        if (is_string($employee->email) && $employee->email !== '' && ExpertiseTerms::contains($employee->email, $query)) {
            return 'email';
        }

        $role = $employee->employeeRole?->name;
        if (is_string($role) && $role !== '' && ExpertiseTerms::contains($role, $query)) {
            return 'role';
        }

        $location = $employee->employeeLocation?->name;
        if (is_string($location) && $location !== '' && ExpertiseTerms::contains($location, $query)) {
            return 'location';
        }

        return null;
    }

    /**
     * @return array{kind: string, name: string, code: ?string, matched_on: string, synonym: ?string, score: int}|null
     */
    private static function catalog(string $kind, Skill|Certificate $catalog, string $query): ?array
    {
        if (! $catalog->is_active) {
            return null;
        }

        $synonyms = ExpertiseTerms::normalize($catalog->synonyms);
        $matched = ExpertiseTerms::match((string) $catalog->name, $catalog->code, $synonyms, $query);
        if ($matched === null) {
            return null;
        }

        $compared = match ($matched['matched_on']) {
            'code' => (string) $catalog->code,
            'synonym' => (string) $matched['synonym'],
            default => (string) $catalog->name,
        };

        return [
            'kind' => $kind,
            'name' => (string) $catalog->name,
            'code' => $catalog->code,
            'matched_on' => $matched['matched_on'],
            'synonym' => $matched['synonym'],
            'score' => self::score($matched['matched_on'], $compared, $query),
        ];
    }

    private static function score(string $matchedOn, string $compared, string $query): int
    {
        $exact = mb_strtolower(trim($compared)) === ExpertiseTerms::needle($query);

        return match ($matchedOn) {
            'name' => $exact ? 100 : 60,
            'code' => $exact ? 100 : 50,
            'synonym' => $exact ? 80 : 40,
            default => 10,
        };
    }
}
