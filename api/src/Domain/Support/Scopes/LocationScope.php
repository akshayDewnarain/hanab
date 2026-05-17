<?php

namespace Domain\Support\Scopes;

use Domain\Support\Singletons\LocationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class LocationScope implements Scope
{
    public function __construct(private LocationContext $context) {}

    public function apply(Builder $builder, Model $model)
    {
        $locationKey = $this->context->getLocationKey();

        if ($locationKey) {
            $builder->where($model->getTable().'.location_id', $locationKey);
        }
    }
}
