<?php

namespace Domain\Support\QueryBuilders;

use Carbon\Carbon;
use Domain\Support\Helpers\BaseHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;

abstract class BaseBuilder extends Builder
{
    /** @var array<int, string> */
    protected array $relations;

    protected ?string $createdAtColumn = 'created_at';

    protected ?string $updatedAtColumn = 'updated_at';

    public function __construct(QueryBuilder $query)
    {
        parent::__construct($query);

        $this->relations = $this->helper()::relations();
    }

    /** {@inheritdoc} */
    public function __call($method, $parameters)
    {
        if (Str::contains($method, 'Count')) {
            $relation = str_replace('Count', '', $method);
            $operator = $parameters[0] === '0' ? '<' : '>=';

            return $this->has($relation, $operator);
        }

        return parent::__call($method, $parameters);
    }

    /** @return class-string<BaseHelper>|BaseHelper */
    abstract public function helper();

    public function createdAtBetween(string|Carbon|null $begin = null, string|Carbon|null $end = null): static
    {
        if (is_null($this->createdAtColumn)) {
            return $this;
        }

        return $this->baseBetween($this->createdAtColumn, $begin, $end);
    }

    public function updatedAtBetween(string|Carbon|null $begin = null, string|Carbon|null $end = null): static
    {
        if (is_null($this->updatedAtColumn)) {
            return $this;
        }

        return $this->baseBetween($this->updatedAtColumn, $begin, $end);
    }

    protected function baseBetween(string $column, int|string|float|Carbon|null $begin = null, int|string|float|Carbon|null $end = null): static
    {
        $table = $this->getModel()
            ->getTable();

        if (! Str::contains($column, $table)) {
            $column = "{$table}.{$column}";
        }

        $begin = $begin instanceof Carbon || is_string($begin)
            ? $this->convertToAppTimezone($begin)
            : $begin;

        $end = $end instanceof Carbon || is_string($end)
            ? $this->convertToAppTimezone($end)
            : $end;

        if ($begin && $end) {
            return $this->whereBetween($column, [$begin, $end]);
        }

        if ($begin && empty($end)) {
            return $this->where($column, '>=', $begin);
        }

        if (empty($begin) && $end) {
            return $this->where($column, '<=', $end);
        }

        return $this;
    }

    private function convertToAppTimezone(Carbon|string|null $value): Carbon|string|null
    {
        if (empty($value)) {
            return null;
        }

        if (! $value instanceof Carbon) {
            $value = Carbon::parse($value);
        }

        return $value->timezone(config('app.timezone'));
    }
}
