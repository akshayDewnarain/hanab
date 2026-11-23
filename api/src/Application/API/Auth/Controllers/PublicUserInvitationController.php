<?php

namespace Application\API\Auth\Controllers;

use Domain\Auth\Support\PasswordPolicy;
use Domain\Auth\Support\TwoFactorService;
use Domain\Auth\Support\UserInvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PublicUserInvitationController
{
    public function preview(Request $request, UserInvitationService $invitations): JsonResponse
    {
        $token = (string) $request->input('token', '');

        if (strlen($token) < 32) {
            return $this->state(['state' => 'invalid']);
        }

        return $this->state($invitations->preview($token));
    }

    public function accept(Request $request, UserInvitationService $invitations, TwoFactorService $twoFactor): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'min:32', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed', 'max:'.PasswordPolicy::MAX, PasswordPolicy::rule()],
        ]);

        try {
            $invitation = $invitations->accept(
                rawToken: $validated['token'],
                password: $validated['password'],
                name: $validated['name'] ?? null,
                ip: $request->ip(),
                userAgent: $request->userAgent(),
            );
        } catch (InvalidArgumentException) {
            return $this->state(['state' => 'invalid'], 422);
        }

        $user = $invitation->invitedUser;
        if ($user === null) {
            return $this->state(['state' => 'invalid'], 422);
        }

        return response()->json([
            'data' => [
                'step' => 'enroll',
                'onboarding_token' => $twoFactor->issueOnboardingToken($user),
                'email' => $user->email,
            ],
        ])->withHeaders($this->headers());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function state(array $payload, int $status = 200): JsonResponse
    {
        if (($payload['state'] ?? 'invalid') === 'invalid') {
            $status = $status === 200 ? 404 : $status;
        }

        return response()->json(['data' => $payload], $status)->withHeaders($this->headers());
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'Referrer-Policy' => 'no-referrer',
            'Cache-Control' => 'no-store',
            'X-Robots-Tag' => 'noindex',
        ];
    }
}
