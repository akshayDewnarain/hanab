<?php

namespace App\Providers;

use Exception;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }


    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Factory::guessFactoryNamesUsing(function(string $modelName) {
            $factoryClass = str_replace('Models', 'Database\\Factories', $modelName) . 'Factory';

            if (class_exists($factoryClass)) {
                return $factoryClass;
            }

            throw new Exception("Factory for model {$modelName} not found");
        });
    }
}
