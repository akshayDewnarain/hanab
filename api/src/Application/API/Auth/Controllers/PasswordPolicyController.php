<?php

namespace Application\API\Auth\Controllers;

use Domain\Auth\Support\PasswordPolicy;
use Domain\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;

class PasswordPolicyController
{
    public function show(): JsonResponse
    {
        return ApiResponse::success('Password policy.', PasswordPolicy::descriptor());
    }
}
