<?php

namespace Application\API\Auth\Controllers;

use Domain\Auth\Support\PersistentLogin;
use Domain\Auth\Support\TwoFactorException;
use Domain\Auth\Support\TwoFactorService;
use Domain\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TwoFactorChallengeController
{
    public function store(Request $request, TwoFactorService $twoFactor, PersistentLogin $login): JsonResponse
    {
        $validated = $request->validate([
            'challenge' => ['required', 'string', 'min:32', 'max:255'],
            'code' => ['required', 'string', 'max:32'],
        ]);

        try {
            $result = $twoFactor->consumeChallenge($validated['challenge'], $validated['code'], $request->ip());
        } catch (TwoFactorException $exception) {
            return ApiResponse::error($exception->getMessage(), null, $exception->status);
        }

        $user = $result['user'];

        if (! $user->hasCompletedSecurity()) {
            return ApiResponse::success('Authenticator enrollment is required.', [
                'step' => 'enroll',
                'onboarding_token' => $twoFactor->issueOnboardingToken($user, $result['remember']),
            ]);
        }

        $login->establish($user, $result['remember']);
        $request->session()->regenerate();

        return ApiResponse::success('Signed in.', [
            'step' => 'authenticated',
            'token' => $twoFactor->issueAccessToken($user),
        ]);
    }
}
