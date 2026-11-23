<?php

namespace App\Providers;

use Domain\Auth\Models\Role;
use Domain\Auth\Models\User;
use Domain\Auth\Models\UserInvitation;
use Domain\Auth\Policies\RolePolicy;
use Domain\Auth\Policies\UserInvitationPolicy;
use Domain\Auth\Policies\UserPolicy;
use Domain\Auth\Support\BootstrapRoleNames;
use Exception;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(UserInvitation::class, UserInvitationPolicy::class);

        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasRole') && $user->hasRole(BootstrapRoleNames::SUPER_ADMIN)) {
                return true;
            }

            return null;
        });

        RateLimiter::for('invitation-preview', fn (Request $request) => Limit::perMinute(20)->by((string) $request->ip()));
        RateLimiter::for('invitation-accept', fn (Request $request) => Limit::perMinute(5)->by((string) $request->ip()));
        RateLimiter::for('password-forgot', fn (Request $request) => Limit::perMinute(5)->by((string) $request->ip()));
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(5)->by((string) $request->ip()));
        RateLimiter::for('mfa-challenge', fn (Request $request) => Limit::perMinute(10)->by((string) $request->ip()));

        Factory::guessFactoryNamesUsing(function(string $modelName) {
            $factoryClass = str_replace('Models', 'Database\\Factories', $modelName) . 'Factory';

            if (class_exists($factoryClass)) {
                return $factoryClass;
            }

            throw new Exception("Factory for model {$modelName} not found");
        });
    }
}
