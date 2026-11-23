<?php

namespace Domain\Employees\Http\Requests\QueryBuilders;

use Domain\Employees\Helpers\EmployeeHelper;
use Domain\Employees\Models\Employee;
use Domain\Support\Http\Requests\QueryBuilders\BaseModelQueryRequest;
use Domain\Support\Http\Requests\QueryBuilders\BaseQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedSort;

abstract class EmployeeQueryRequest extends BaseModelQueryRequest
{
    public function queryBuilder(): BaseQueryBuilder
    {
        $builder = $this->baseQueryBuilder(Employee::class)
            ->allowedFilters(EmployeeHelper::filterable())
            ->allowedIncludes(EmployeeHelper::relations())
            ->allowedSorts(EmployeeHelper::sortable())
            ->search(EmployeeHelper::searchable());

        $term = trim((string) $this->input('search', ''));
        if ($term !== '' && ! $this->filled('sort') && $this->input('filter.is_active') === null) {
            $builder->where((new Employee())->getTable().'.is_active', true);
        }

        if ($term !== '' && ! $this->filled('sort')) {
            $builder->defaultSort(AllowedSort::callback('relevance', function (Builder $query) use ($term): void {
                $query->reorder();
                $query->orderByColleagueRelevance($term);
            }));
        } else {
            $builder->defaultSort('last_name');
        }

        return $builder;
    }
}
