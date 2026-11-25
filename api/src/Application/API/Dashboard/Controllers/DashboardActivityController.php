<?php

namespace Application\API\Dashboard\Controllers;

use Domain\Auth\Models\User;
use Domain\Dashboard\Actions\ListDashboardActivityAction;
use Domain\Support\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardActivityController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $limit = (int) $request->query('limit', 8);
        $cursor = $request->query('cursor');
        $after = $request->query('after');

        return response()->json(ListDashboardActivityAction::make()->execute(
            $user,
            $limit,
            is_string($cursor) ? $cursor : null,
            is_string($after) ? $after : null,
        ));
    }
}
