<?php

namespace Domain\Support\Http\Controllers;

use Domain\Auth\Models\User;
use Domain\Support\DataTransferObjects\ActionResult;
use Domain\Support\DataTransferObjects\ModelActionCheckData;
use Domain\Support\Enums\ActionCheckMessageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

abstract class BaseActionController extends Controller
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    /**
     * Return a JSON response based on the action result.
     */
    protected function response(PendingDispatch|ActionResult $actionResult): JsonResponse
    {
        if ($actionResult instanceof PendingDispatch) {
            return response()
                ->json([
                    'queued' => true,
                ]);
        }

        return response()
            ->json([
                'queued' => false,
                'data' => $actionResult->data,
            ]);
    }

    protected function checkModel(Model $model, User $user, string $policyMethod, ?array $rules = null, ?array $messages = null): ModelActionCheckData
    {
        if ($user->cannot($policyMethod, $model)) {
            return new ModelActionCheckData($model, false, __('ACTION_IS_UNAUTHORIZED'), ActionCheckMessageType::Error);
        }

        if ($rules) {
            try {
                $this->checkModelWithValidator($model, $rules, $messages);
            } catch (ValidationException $e) {
                Log::error($e);

                return new ModelActionCheckData($model, false, __($e->getMessage()), ActionCheckMessageType::Error);
            }
        }

        return new ModelActionCheckData($model, true);
    }
}
