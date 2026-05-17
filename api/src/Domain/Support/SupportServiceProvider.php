<?php

namespace Domain\Support;

use Domain\Support\Singletons\LocationContext;
use Illuminate\Support\ServiceProvider;

class SupportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LocationContext::class, fn () => new LocationContext);
    }

    public function boot(): void {}
}
