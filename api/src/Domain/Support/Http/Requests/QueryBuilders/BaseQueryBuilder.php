<?php

namespace Domain\Support\Http\Requests\QueryBuilders;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\Filters\FiltersExact;
use Spatie\QueryBuilder\Filters\FiltersPartial;
use Spatie\QueryBuilder\QueryBuilder;

abstract class BaseQueryBuilder extends QueryBuilder
{
    /**
     * Retrieve a paginated collection of items from the model.
     */
    public function retrieve(): LengthAwarePaginator|Collection
    {
        return static::paginate();
    }

    /**
     * Paginate the model's items based on the request parameters or defaults.
     * Adds the current query parameters to pagination links.
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $total = null): LengthAwarePaginator
    {
        $perPage ??= $this->request->input('perPage', $this->request->input('per_page', 15));

        $paginated = parent::paginate($perPage, $columns, $pageName, $page);

        return $paginated->appends($this->request->query());
    }

    /**
     * Define allowed filters for the query based on the model's mapping.
     */
    public function allowedFilters($filters, $overwrite = false): static
    {
        $map = $this->getModelMap();

        if (! $map) {
            return parent::allowedFilters($filters);
        }

        return parent::allowedFilters(
            collect($filters)
                ->map(fn ($filter) => is_string($filter)
                    ? AllowedFilter::partial($filter, $map->get($filter))
                    : $this->resolveFilter($filter, $map)
                )
                ->all()
        );
    }

    /**
     * Define default sorting for the query based on the model's mapping.
     */
    public function defaultSorts($sorts): static
    {
        $map = $this->getModelMap();
        if (! $map) {
            return parent::defaultSorts($sorts);
        }

        return parent::defaultSorts(
            collect($sorts)
                ->map(function (string $sort) use ($map) {
                    $descending = $sort[0] === '-';
                    $key = mb_ltrim($sort, '-');
                    if (! $map->has($key)) {
                        return $sort;
                    }
                    $sort = $map->get($key);
                    if ($descending) {
                        $sort = "-{$sort}";
                    }

                    return $sort;
                })->all()
        );
    }

    /**
     * Define allowed sorting options based on the model's mapping.
     */
    public function allowedSorts($sorts): static
    {
        $map = $this->getModelMap();

        if (! $map) {
            return parent::allowedSorts($sorts);
        }

        return parent::allowedSorts(
            collect($sorts)
                ->map(fn ($sort) => is_string($sort)
                    ? AllowedSort::field($sort, $map->get($sort))
                    : $sort
                )->all()
        );
    }

    /**
     * Search the model based on the provided columns.
     */
    public function search(array $searchColumns = [], ?Builder $baseQuery = null, ?Model $model = null): static
    {
        $searchTerm = $this->request->input('search');

        if ($searchTerm) {
            $baseQuery = $baseQuery ?? $this->subject;

            $baseQuery->where(function (Builder $query) use ($searchColumns, $searchTerm, $model) {
                $model = $model ?? $this->subject->getModel();

                foreach ($searchColumns as $relation => $searchColumn) {
                    if (is_array($searchColumn)) {
                        $relationModel = $model->{$relation}()->getModel();

                        $query->orWhereHas($relation, function ($query) use ($searchColumn, $relationModel) {
                            $this->search($searchColumn, $query, $relationModel);
                        });
                    } else {
                        $searchTerm = Str::replace(' ', '%', $searchTerm);
                        $query->orWhere($searchColumn, 'LIKE', "%{$searchTerm}%");
                    }
                }
            });
        }

        return $this;
    }

    /**
     * Retrieve the model's mapping for filters and sorts, if available.
     */
    protected function getModelMap(): ?Collection
    {
        $model = $this->getModel();

        if (! $model) {
            return null;
        }

        if (! method_exists($model, 'getMap')) {
            return null;
        }

        return $model->getMap();
    }

    /**
     * Resolve the appropriate filter type based on the model's filter class.
     */
    protected function resolveFilter(AllowedFilter $filter, Collection $map): AllowedFilter
    {
        $filterClass = $filter->getFilterClass();
        $filterName = $filter->getName();

        return match (true) {
            $filterClass instanceof FiltersPartial => $filter::partial($filter->getName(), $map->get($filterName)),
            $filterClass instanceof FiltersExact => $filter::exact($filter->getName(), $map->get($filterName)),
            default => $filter,
        };
    }
}
