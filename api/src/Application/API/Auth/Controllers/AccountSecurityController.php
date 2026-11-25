<?php

namespace Application\API\Auth\Controllers;

use Domain\Auth\Models\User;
use Domain\Auth\Support\PasswordPolicy;
use Domain\Auth\Support\TwoFactorException;
use Domain\Auth\Support\TwoFactorService;
use Domain\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AccountSecurityController
{
    public function show(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $remaining = $user->recoveryCodes()->whereNull('used_at')->count();

        return ApiResponse::success('Security status.', [
            'password_set_at' => $user->password_set_at?->toIso8601String(),
            'two_factor_confirmed_at' => $user->two_factor_confirmed_at?->toIso8601String(),
            'onboarding_completed_at' => $user->onboarding_completed_at?->toIso8601String(),
            'recovery_codes_remaining' => $remaining,
            'two_factor_enabled' => $user->hasCompletedSecurity(),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'max:'.PasswordPolicy::MAX],
            'password' => ['required', 'string', 'confirmed', PasswordPolicy::rule()],
        ]);

        if (! password_verify($validated['current_password'], (string) $user->password)) {
            return ApiResponse::error('The current password is incorrect.');
        }

        $user->forceFill([
            'password' => $validated['password'],
            'password_set_at' => now(),
        ]);
        $user->setRememberToken(Str::random(60));
        $user->save();
        $user->tokens()->where('name', 'onboarding')->delete();

        activity('security')->performedOn($user)->withProperties(['event' => 'password_changed'])->log('password_changed');

        return ApiResponse::success('Password updated.');
    }

    public function regenerate(Request $request, TwoFactorService $twoFactor): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'max:'.PasswordPolicy::MAX],
            'code' => ['required', 'string', 'max:32'],
        ]);

        try {
            $codes = $twoFactor->regenerateRecoveryCodes(
                $this->user($request),
                $validated['current_password'],
                $validated['code'],
                $request->ip(),
            );
        } catch (TwoFactorException $exception) {
            return ApiResponse::error($exception->getMessage(), null, $exception->status);
        }

        return ApiResponse::success('New recovery codes are ready. The previous codes no longer work.', [
            'recovery_codes' => $codes,
        ]);
    }

    public function replace(Request $request, TwoFactorService $twoFactor): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'max:'.PasswordPolicy::MAX],
            'code' => ['required', 'string', 'max:32'],
        ]);

        try {
            $setup = $twoFactor->beginReplacement(
                $this->user($request),
                $validated['current_password'],
                $validated['code'],
                $request->ip(),
            );
        } catch (TwoFactorException $exception) {
            return ApiResponse::error($exception->getMessage(), null, $exception->status);
        }

        return ApiResponse::success('Scan the new authenticator, then confirm it.', $setup);
    }

    public function cancelReplacement(Request $request, TwoFactorService $twoFactor): JsonResponse
    {
        $twoFactor->cancelReplacement($this->user($request));

        return ApiResponse::success('Authenticator replacement was cancelled. The current authenticator is unchanged.');
    }

    public function confirmReplacement(Request $request, TwoFactorService $twoFactor): JsonResponse
    {
        $code = (string) $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ])['code'];

        try {
            $codes = $twoFactor->confirmEnrollment($this->user($request), $code, $request->ip());
        } catch (TwoFactorException $exception) {
            return ApiResponse::error($exception->getMessage(), null, $exception->status);
        }

        return ApiResponse::success('The authenticator was replaced. Save the new recovery codes.', [
            'recovery_codes' => $codes,
        ]);
    }

    public function resetForUser(Request $request, User $user, TwoFactorService $twoFactor): JsonResponse
    {
        $actor = $this->user($request);
        if (! $actor->hasRole('super-admin') || $actor->is($user)) {
            return ApiResponse::error('Another administrator has to recover this account.', null, 403);
        }

        $twoFactor->clearSecondFactor($user);

        return ApiResponse::success('The authenticator was cleared. The user enrolls again at the next sign-in.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
