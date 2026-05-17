<?php

namespace Domain\Support\Http\Requests\QueryBuilders;

use Domain\Support\Http\Requests\BaseQueryRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedInclude;

abstract class BaseModelQueryRequest extends BaseQueryRequest
{
    abstract public function queryBuilder(): BaseQueryBuilder;

    /**
     * This should return the model query builder class for the index function of the controller.
     *
     * @return BaseQueryBuilder|class-string<BaseQueryBuilder>
     */
    abstract protected function queryClass(): mixed;

    protected function baseQueryBuilder(Builder|Relation|string $subject): BaseQueryBuilder
    {
        return $this->queryClass()::for($subject);
    }

    /**
     * @param  array<string>  $relations
     * @return array<Collection>
     */
    protected function relationCounts(array $relations): array
    {
        return array_map(fn (string $relation): Collection => AllowedInclude::count($relation), $relations);
    }
}
