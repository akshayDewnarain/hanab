<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RouteMacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Route::macro('apiResourceImageActions', function (string $name, string $param, string $delete) {
            Route::delete("$name/{{$param}}/image", $delete)->name("$name.image.destroy");
        });
    }
}
