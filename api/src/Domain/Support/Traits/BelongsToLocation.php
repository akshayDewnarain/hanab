<?php

namespace Domain\Support\Traits;

use Domain\General\Models\Location;
use Domain\Support\Scopes\LocationScope;
use Domain\Support\Singletons\LocationContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Make static analyzers treat this trait as if it's mixed into an Eloquent Model.
 *
 * @mixin Model
 */
trait BelongsToLocation
{
    public static function bootBelongsToLocation(): void
    {
        static::addGlobalScope(app(LocationScope::class));

        static::creating(function (Model $model): void {
            $locationKey = app(LocationContext::class)->getLocationKey();

            if ($locationKey) {
                $model->setAttribute('location_id', $locationKey);
            }
        });
    }

    public static function withoutLocationScope(): static
    {
        return static::withoutGlobalScope(LocationScope::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
