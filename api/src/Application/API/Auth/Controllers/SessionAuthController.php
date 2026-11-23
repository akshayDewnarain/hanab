<?php

namespace Application\API\Auth\Controllers;

use Domain\Auth\Http\Requests\LoginRequest;
use Domain\Auth\Models\User;
use Domain\Auth\Support\TwoFactorService;
use Domain\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Password verification starts a second step. It does not open a session
 * or issue an access-api token. The challenge or enrollment endpoints do that.
 */
class SessionAuthController
{
    public function login(LoginRequest $request, TwoFactorService $twoFactor): JsonResponse
    {
        $credentials = $request->validated();
        $remember = $request->boolean('remember');
        $guard = Auth::guard('web');

        if ($guard->check()) {
            $guard->logout();
        }

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user instanceof User || ! $user->canSignIn() || ! Hash::check($credentials['password'], (string) $user->password)) {
            $this->audit($user instanceof User ? $user : null, 'login_failed', [
                'email' => $credentials['email'],
            ]);

            return ApiResponse::error(__('INVALID_CREDENTIALS'));
        }

        if ($user->two_factor_confirmed_at === null || ! $user->hasCompletedSecurity()) {
            return ApiResponse::success('Authenticator setup is required.', [
                'step' => 'enroll',
                'onboarding_token' => $twoFactor->issueOnboardingToken($user, $remember),
            ]);
        }

        return ApiResponse::success('Two-factor verification is required.', [
            'step' => 'challenge',
            'challenge' => $twoFactor->issueChallenge($user, $remember),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user instanceof User) {
            $this->audit($user, 'logout');
        }

        $user?->tokens()->delete();
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ApiResponse::success(__('LOGGED_OUT_SUCCESSFULLY'));
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function audit(?User $user, string $event, array $properties = []): void
    {
        try {
            $log = activity('auth')->event($event)->withProperties($properties);
            if ($user instanceof User) {
                $log->causedBy($user)->performedOn($user);
            }
            $log->log($event);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
