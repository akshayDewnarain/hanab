<?php

namespace Application\API\Auth\Middleware;

use Closure;
use Domain\Auth\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\TransientToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Full application access requires a completed authenticator setup.
 * A Sanctum token must list the access-api ability explicitly. A wildcard
 * ability does not pass, so tokens issued before MFA cannot be reused.
 * Session authentication used by the test guard is allowed only after the
 * user record itself has completed setup. Role checks, including super-admin,
 * are not consulted here.
 */
class EnsureSecurityComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->hasCompletedSecurity()) {
            return $this->deny();
        }

        $token = $user->currentAccessToken();
        if ($token !== null && ! $token instanceof TransientToken) {
            $abilities = $token->abilities ?? [];
            if (! in_array('access-api', $abilities, true)) {
                return $this->deny();
            }
        }

        return $next($request);
    }

    private function deny(): Response
    {
        return response()->json([
            'message' => 'MFA_REQUIRED',
            'code' => 'mfa_required',
        ], 403);
    }
}
