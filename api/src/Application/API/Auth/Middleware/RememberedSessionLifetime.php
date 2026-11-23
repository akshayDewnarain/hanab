<?php

namespace Application\API\Auth\Middleware;

use Closure;
use Domain\Auth\Support\PersistentLogin;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A remembered sign-in keeps the server session for the same 30 days as the cookie.
 * The cookie is only present after a completed authenticator check.
 */
class RememberedSessionLifetime
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('web');
        if ($guard instanceof SessionGuard && $request->cookies->has($guard->getRecallerName())) {
            config([
                'session.lifetime' => PersistentLogin::minutes(),
                'session.expire_on_close' => false,
            ]);
        }

        return $next($request);
    }
}
