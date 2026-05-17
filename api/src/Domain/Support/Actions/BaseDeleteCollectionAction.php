<?php

namespace Domain\Support\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class BaseDeleteCollectionAction extends BaseQueuedCollectionAction
{
    public function execute(CollectionActionInputData $data): CollectionActionDeleteResult
    {
        $models = $data->checkData->eligible;
        $collectionActionDeleteData = new CollectionActionDeleteData([], $data->checkData->ineligible);

        foreach ($data->checkData->eligible as $model) {
            if (! $this->user?->can('delete', $model)) {
                $models = $models->filter(fn (Model $m) => $m->getKey() !== $model->getKey());

                $collectionActionDeleteData->failed->push($model);

                continue;
            }

            try {
                // get the primary key before the model is deleted
                $modelKey = $model->getKey();

                $this->deleteAction()
                    ->execute($model);

                $collectionActionDeleteData->succeeded[] = $modelKey;
            } catch (Throwable $e) {
                Log::error($e);

                $collectionActionDeleteData->failed->push($model);
            }
        }

        return new CollectionActionDeleteResult($collectionActionDeleteData);
    }
}
