<?php

namespace Application\API\Auth\Controllers;

use Application\API\Auth\Requests\ApiResetPasswordRequest;
use Application\API\Auth\Requests\ForgotPasswordRequest;
use Domain\Auth\Models\User;
use Domain\Support\Helpers\ApiResponse;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function sendResetLinkEmail(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->validated('email');

        $user = User::query()->where('email', $email)->first();
        if ($user) {
            $status = Password::broker()->sendResetLink(['email' => $email]);
            if ($status === Password::RESET_THROTTLED) {
                return ApiResponse::error(__('Please wait before requesting another reset link.'), null, 429);
            }
        }

        return ApiResponse::success(__('If an account exists for that email, we have sent password reset instructions.'));
    }

    public function reset(ApiResetPasswordRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $status = Password::broker()->reset(
            $credentials,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                ]);
                $user->setRememberToken(Str::random(60));
                $user->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return ApiResponse::success(__('Your password has been reset. You can sign in with your new password.'));
        }

        if ($status === Password::INVALID_TOKEN) {
            return ApiResponse::error(__('This password reset link is invalid or has expired. Please request a new one.'), [
                'token' => [__('This password reset link is invalid or has expired. Please request a new one.')],
            ], 422);
        }

        if ($status === Password::INVALID_USER) {
            return ApiResponse::error(__('This password reset link is invalid or has expired. Please request a new one.'), null, 422);
        }

        if ($status === Password::RESET_THROTTLED) {
            return ApiResponse::error(__('Please wait before trying again.'), null, 429);
        }

        return ApiResponse::error(__('Could not reset the password. Please try again.'), null, 422);
    }
}
