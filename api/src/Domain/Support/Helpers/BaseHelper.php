<?php

namespace Domain\Support\Helpers;

use Exception;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\Filters\Filter;

abstract class BaseHelper
{
    /**
     * Overwrite this function to declare which properties are searchable.
     *
     * @return array<int, string>
     */
    public static function searchable(): array
    {
        return [];
    }

    /**
     * Overwrite this function to declare which properties are searchable.
     *
     * @return array<int, string>
     */
    public static function sortable(): array
    {
        return [
            'id',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Overwrite this function to declare which properties are filterable.
     *
     * @return array<int, Filter|string>
     */
    public static function filterable(): array
    {
        $filterable = [
            AllowedFilter::exact('id'),
            AllowedFilter::scope('created_at_between'),
            AllowedFilter::scope('updated_at_between'),
        ];

        return $filterable;
    }

    /**
     * Overwrite this function to declare which relations are allowed to be loaded.
     *
     * @return array<int, mixed>
     */
    public static function relations(): array
    {
        return [];
    }

    /**
     * Returns the relations that are allowed to be loaded.
     *
     * @return array<int, string>
     *
     * @throws Exception
     */
    public static function relationsToLoad(): array
    {
        $relations = HelperFunctions::flatNestedProperties(static::relations());
        $requestRelations = request()->input('include');

        if (empty($requestRelations)) {
            return [];
        }

        $list = explode(',', $requestRelations);

        $validRelations = [];
        foreach ($requestRelations as $relation) {
            if (! in_array($relation, $relations)) {
                throw new Exception("Invalid relation '{$relation}' requested.");
            }
            $validRelations = $relation;
        }

        return $validRelations;
    }
}
