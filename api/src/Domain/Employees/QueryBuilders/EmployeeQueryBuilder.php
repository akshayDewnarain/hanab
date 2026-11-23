<?php

namespace Domain\Employees\QueryBuilders;

use Domain\Employees\Helpers\EmployeeHelper;
use Domain\Employees\Support\ExpertiseTerms;
use Domain\Support\QueryBuilders\BaseBuilder;
use Illuminate\Database\Eloquent\Builder;

class EmployeeQueryBuilder extends BaseBuilder
{
    public function helper(): string
    {
        return EmployeeHelper::class;
    }

    /**
     * Active colleagues whose name, email, role, location, or expertise contains the term.
     */
    public function matchingColleague(string $term): static
    {
        $needle = ExpertiseTerms::needle($term);
        if ($needle === '') {
            return $this->whereRaw('0 = 1');
        }

        $pattern = '%'.$needle.'%';
        $first = $this->qualifyColumn('first_name');
        $last = $this->qualifyColumn('last_name');
        $email = $this->qualifyColumn('email');

        return $this->where($this->qualifyColumn('is_active'), true)
            ->where(function (Builder $query) use ($pattern, $first, $last, $email): void {
                $query->whereRaw('LOWER(CONCAT('.$first.', \' \', '.$last.')) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(COALESCE('.$email.', \'\')) LIKE ?', [$pattern])
                    ->orWhereHas(
                        'employeeRole',
                        fn (Builder $roleQuery) => $roleQuery->whereRaw(
                            'LOWER('.$roleQuery->qualifyColumn('name').') LIKE ?',
                            [$pattern],
                        ),
                    )
                    ->orWhereHas(
                        'employeeLocation',
                        fn (Builder $locationQuery) => $locationQuery->whereRaw(
                            'LOWER('.$locationQuery->qualifyColumn('name').') LIKE ?',
                            [$pattern],
                        ),
                    )
                    ->orWhereHas(
                        'skills',
                        fn (Builder $skillQuery) => $this->whereExpertiseColumns($skillQuery, $pattern),
                    )
                    ->orWhereHas(
                        'certificates',
                        fn (Builder $certificateQuery) => $this->whereExpertiseColumns($certificateQuery, $pattern),
                    );
            });
    }

    /**
     * Higher buckets first: exact catalog name or code, then a partial catalog hit, then a name, role, or location.
     */
    public function orderByColleagueRelevance(string $term): static
    {
        $needle = ExpertiseTerms::needle($term);
        if ($needle === '') {
            return $this;
        }

        $exact = $needle;
        $like = '%'.$needle.'%';
        $table = $this->getModel()->getTable();
        $score = '(CASE
            WHEN EXISTS (
                SELECT 1 FROM employee_skills es
                INNER JOIN skills s ON s.id = es.skill_id
                WHERE es.employee_id = '.$table.'.id AND s.is_active = 1
                  AND (LOWER(s.name) = ? OR LOWER(COALESCE(s.code, \'\')) = ?)
            ) OR EXISTS (
                SELECT 1 FROM employee_certificates ec
                INNER JOIN certificates c ON c.id = ec.certificate_id
                WHERE ec.employee_id = '.$table.'.id AND c.is_active = 1
                  AND (LOWER(c.name) = ? OR LOWER(COALESCE(c.code, \'\')) = ?)
            ) THEN 4
            WHEN EXISTS (
                SELECT 1 FROM employee_skills es
                INNER JOIN skills s ON s.id = es.skill_id
                WHERE es.employee_id = '.$table.'.id AND s.is_active = 1
                  AND (LOWER(s.name) LIKE ? OR LOWER(COALESCE(s.code, \'\')) LIKE ? OR LOWER(COALESCE(s.synonyms, \'\')) LIKE ?)
            ) OR EXISTS (
                SELECT 1 FROM employee_certificates ec
                INNER JOIN certificates c ON c.id = ec.certificate_id
                WHERE ec.employee_id = '.$table.'.id AND c.is_active = 1
                  AND (LOWER(c.name) LIKE ? OR LOWER(COALESCE(c.code, \'\')) LIKE ? OR LOWER(COALESCE(c.synonyms, \'\')) LIKE ?)
            ) THEN 2
            ELSE 1 END)';

        return $this->orderByRaw($score.' DESC', [
            $exact, $exact, $exact, $exact,
            $like, $like, $like, $like, $like, $like,
        ])->orderBy($this->qualifyColumn('last_name'))
            ->orderBy($this->qualifyColumn('first_name'));
    }

    /**
     * Active employees linked to an active skill or certificate whose name, code, or synonym contains the term.
     */
    public function matchingExpertise(string $term): static
    {
        $needle = ExpertiseTerms::needle($term);
        if ($needle === '') {
            return $this->whereRaw('0 = 1');
        }

        $pattern = '%'.$needle.'%';

        return $this->where($this->qualifyColumn('is_active'), true)
            ->where(function (Builder $query) use ($pattern): void {
                $query->whereHas(
                    'skills',
                    fn (Builder $skillQuery) => $this->whereExpertiseColumns($skillQuery, $pattern),
                )->orWhereHas(
                    'certificates',
                    fn (Builder $certificateQuery) => $this->whereExpertiseColumns($certificateQuery, $pattern),
                );
            });
    }

    private function whereExpertiseColumns(Builder $query, string $pattern): void
    {
        $name = $query->qualifyColumn('name');
        $code = $query->qualifyColumn('code');
        $synonyms = $query->qualifyColumn('synonyms');
        $active = $query->qualifyColumn('is_active');

        $query->where($active, true)
            ->where(function (Builder $inner) use ($name, $code, $synonyms, $pattern): void {
                $inner->whereRaw('LOWER('.$name.') LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(COALESCE('.$code.', \'\')) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(COALESCE('.$synonyms.', \'\')) LIKE ?', [$pattern]);
            });
    }
}
