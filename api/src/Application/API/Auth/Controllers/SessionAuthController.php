<?php

namespace Application\API\Auth\Controllers;

use Domain\Auth\Http\Requests\LoginRequest;
use Domain\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Cookie + session auth for first-party SPAs (Sanctum stateful).
 *
 * - SPA: axios/fetch withCredentials, call GET /sanctum/csrf-cookie before POST /api/v1/login.
 * - PAT: send JSON "device_name" to receive a plain-text token (store securely; not wired from localStorage here).
 *
 * @see cursor/backend-sanctum-spa.md
 */
class SessionAuthController
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            return ApiResponse::error(__('INVALID_CREDENTIALS'));
        }

        $user  = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success(__('LOGGED_IN_SUCCESSFULLY'), [
            'token' => $token,
            'user'  => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();
        $request->session()->invalidate();

        return ApiResponse::success(__('LOGGED_OUT_SUCCESSFULLY'));
    }
}
