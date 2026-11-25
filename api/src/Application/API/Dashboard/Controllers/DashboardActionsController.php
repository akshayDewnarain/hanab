<?php

namespace Application\API\Dashboard\Controllers;

use Domain\Auth\Models\User;
use Domain\Dashboard\Actions\ListDashboardActionsAction;
use Domain\Support\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardActionsController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return response()->json([
            'data' => ListDashboardActionsAction::make()->execute($user),
        ]);
    }
}
