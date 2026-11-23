<?php

namespace Application\API\Auth\Controllers;

use Domain\Auth\Models\User;
use Domain\Auth\Support\PersistentLogin;
use Domain\Auth\Support\TwoFactorException;
use Domain\Auth\Support\TwoFactorService;
use Domain\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class TwoFactorEnrollmentController
{
    public function start(Request $request, TwoFactorService $twoFactor): JsonResponse
    {
        return $this->guard($request, function (User $user) use ($twoFactor) {
            return ApiResponse::success('Authenticator setup is ready.', $twoFactor->beginEnrollment($user));
        });
    }

    public function confirm(Request $request, TwoFactorService $twoFactor): JsonResponse
    {
        $code = (string) $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ])['code'];

        return $this->guard($request, function (User $user) use ($twoFactor, $code, $request) {
            $codes = $twoFactor->confirmEnrollment($user, $code, $request->ip());

            return ApiResponse::success('Authenticator confirmed. Save the recovery codes.', [
                'recovery_codes' => $codes,
            ]);
        });
    }

    public function acknowledge(Request $request, TwoFactorService $twoFactor): JsonResponse
    {
        $confirmed = $request->validate([
            'saved' => ['required', 'accepted'],
        ]);
        unset($confirmed);

        return $this->guard($request, function (User $user) use ($twoFactor, $request) {
            $remember = $this->remembersLogin($user);
            $twoFactor->acknowledge($user);
            $user->tokens()->where('name', 'onboarding')->delete();
            app(PersistentLogin::class)->establish($user, $remember);
            $request->session()->regenerate();

            return ApiResponse::success('Account activated.', [
                'step' => 'authenticated',
                'token' => $twoFactor->issueAccessToken($user->fresh() ?? $user),
            ]);
        });
    }

    private function remembersLogin(User $user): bool
    {
        $token = $user->currentAccessToken();
        if (! $token instanceof PersonalAccessToken) {
            return false;
        }

        return in_array('remember', $token->abilities ?? [], true);
    }

    private function guard(Request $request, callable $callback): JsonResponse
    {
        try {
            return $callback($this->actor($request));
        } catch (TwoFactorException $exception) {
            return ApiResponse::error($exception->getMessage(), null, $exception->status);
        }
    }

    private function actor(Request $request): User
    {
        $plain = $request->bearerToken();
        if (is_string($plain) && $plain !== '') {
            $accessToken = PersonalAccessToken::findToken($plain);
            $abilities = $accessToken?->abilities ?? [];
            $user = $accessToken?->tokenable;
            if (
                $accessToken !== null
                && $user instanceof User
                && in_array('onboarding', $abilities, true)
                && ($accessToken->expires_at === null || $accessToken->expires_at->isFuture())
            ) {
                return $user->withAccessToken($accessToken);
            }
        }

        $sessionUser = $request->user();
        if ($sessionUser instanceof User && ! $sessionUser->hasCompletedSecurity()) {
            return $sessionUser;
        }

        abort(401);
    }
}
