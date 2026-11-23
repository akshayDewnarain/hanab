<?php

namespace Application\API\Employees\Controllers;

use Domain\Auth\Models\User;
use Domain\Employees\Actions\BuildOrganogramAction;
use Domain\Employees\Http\Requests\OrganogramQueryRequest;
use Domain\Support\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;

class OrganogramController extends BaseController
{
    public function show(OrganogramQueryRequest $request): JsonResponse
    {
        $viewer = $request->user();
        $graph = BuildOrganogramAction::make()->execute(
            $request->viewName(),
            $request->locationId(),
            $request->levelFilter(),
            $viewer instanceof User ? $viewer : null,
        );

        return response()->json([
            'data' => $graph,
            'meta' => [
                'view' => $request->viewName(),
                'totalNodes' => count($graph['nodes']),
            ],
        ]);
    }
}
