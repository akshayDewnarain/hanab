<?php

namespace Application\API\Support\Controllers;

use Application\API\Support\Requests\TransferImportRequest;
use Domain\Auth\Services\PermissionService;
use Domain\Auth\Support\Maintainer;
use Domain\Support\Http\Controllers\BaseController;
use Domain\Support\Transfer\Actions\CommitTransferImportAction;
use Domain\Support\Transfer\Actions\DescribeTransferFieldsAction;
use Domain\Support\Transfer\Actions\ExportTransferRowsAction;
use Domain\Support\Transfer\Actions\ListTransferModelsAction;
use Domain\Support\Transfer\Actions\PreviewTransferImportAction;
use Domain\Support\Transfer\TransferCatalog;
use Domain\Support\Transfer\TransferModelNotFound;
use Illuminate\Http\JsonResponse;

class TransfersController extends BaseController
{
    public function models(): JsonResponse
    {
        return response()->json([
            'data' => ListTransferModelsAction::make()->execute(),
        ]);
    }

    public function fields(string $transferModel): JsonResponse
    {
        return response()->json([
            'data' => $this->guard(fn () => DescribeTransferFieldsAction::make()->execute($transferModel)),
        ]);
    }

    public function rows(string $transferModel): JsonResponse
    {
        $this->authorizeTransfer($transferModel);

        return response()->json([
            'data' => $this->guard(fn () => ExportTransferRowsAction::make()->execute($transferModel)),
        ]);
    }

    public function preview(TransferImportRequest $request, string $transferModel): JsonResponse
    {
        $this->authorizeTransfer($transferModel);

        return response()->json([
            'data' => $this->guard(fn () => PreviewTransferImportAction::make()->execute(
                $transferModel,
                $request->validated('rows'),
                $request->validated('strategy'),
                $request->validated('policy'),
                $request->user(),
            )),
        ]);
    }

    public function commit(TransferImportRequest $request, string $transferModel): JsonResponse
    {
        $this->authorizeTransfer($transferModel);

        $result = $this->guard(fn () => CommitTransferImportAction::make()->execute(
            $transferModel,
            $request->validated('rows'),
            $request->validated('file_name'),
            $request->user()?->id,
            $request->validated('strategy'),
            $request->validated('policy'),
            $request->user(),
            (bool) $request->boolean('confirm_partial'),
        ));

        $status = ($result['saved'] ?? false) ? 200 : 422;

        return response()->json(['data' => $result], $status);
    }

    private function authorizeTransfer(string $transferModel): void
    {
        try {
            $permission = (new TransferCatalog)->maintainPermission($transferModel);
        } catch (TransferModelNotFound) {
            return;
        }

        $user = request()->user();
        if ($transferModel === 'employees') {
            if (! Maintainer::allows($user, $permission)) {
                abort(403);
            }

            return;
        }

        try {
            $model = (new TransferCatalog)->find($transferModel);
        } catch (TransferModelNotFound) {
            return;
        }

        $create = Maintainer::allows($user, PermissionService::createPermission($model->modelClass));
        $update = Maintainer::allows($user, PermissionService::updatePermission($model->modelClass));
        if (! $create && ! $update) {
            abort(403);
        }
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function guard(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (TransferModelNotFound) {
            abort(404);
        }
    }
}
