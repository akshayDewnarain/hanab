<?php

namespace Application\API\Dashboard\Controllers;

use Domain\Auth\Models\User;
use Domain\Auth\Support\Maintainer;
use Domain\Employees\Actions\SummarizePeopleOverviewAction;
use Domain\Support\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PeopleOverviewController extends BaseController
{
    public function show(Request $request): JsonResponse
    {
        $data = SummarizePeopleOverviewAction::make()->execute();
        $user = $request->user();
        $quality = Maintainer::allows($user instanceof User ? $user : null, 'update employees');
        if (! $quality) {
            unset(
                $data['without_skills'],
                $data['without_certificates'],
                $data['without_location'],
                $data['without_email'],
                $data['without_phone'],
                $data['with_skills'],
                $data['with_certificates'],
                $data['with_location'],
                $data['with_email'],
                $data['with_phone'],
                $data['skills_percent'],
                $data['certificates_percent'],
                $data['location_percent'],
                $data['email_percent'],
                $data['phone_percent'],
            );
        }

        return response()->json([
            'data' => [
                ...$data,
                'quality' => $quality,
            ],
        ]);
    }
}
