<?php

namespace Domain\Support\Http\Controllers;

use Domain\Support\Actions\BaseQueuedCollectionAction;
use Domain\Support\DataTransferObjects\CollectionActionCheckData;
use Domain\Support\DataTransferObjects\CollectionActionCheckResult;
use Domain\Support\DataTransferObjects\CollectionActionData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseDeleteCollectionActionController extends BaseCollectionActionController
{
    /**
     * Override if you want to add custom deletion rules
     *
     * @var array<string, mixed>
     */
    protected array $rules = [];

    abstract protected function query(): Builder;

    abstract protected function action(): BaseQueuedCollectionAction;

    public function check(CollectionActionData $collectionActionData, Request $request): JsonResponse
    {
        $checkResult = new CollectionActionCheckResult($this->checkDeleteData($collectionActionData, $request));

        return $this->response($checkResult);
    }

    private function checkDeleteData(CollectionActionData $collectionActionData, Request $request): CollectionActionCheckData
    {
        return $this->checkModels(
            $collectionActionData,
            $request->user(),
            $this->query(),
            'delete',
            $this->rules,
        );
    }
}
