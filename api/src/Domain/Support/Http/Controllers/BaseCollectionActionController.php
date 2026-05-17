<?php

namespace Domain\Support\Http\Controllers;

use Domain\Auth\Models\User;
use Domain\Support\DataTransferObjects\ActionCollectionData;
use Domain\Support\DataTransferObjects\CollectionActionCheckData;
use Domain\Support\DataTransferObjects\CollectionActionData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

abstract class BaseCollectionActionController extends BaseActionController
{
    protected function mapIdsToCollection(CollectionActionData $data, Builder $query): ActionCollectionData
    {
        $model = $query->getModel();

        if (method_exists($model, 'getHelper')) {
            $modelMap = $model->getHelper();
            $idColumn = $modelMap->get('id');
        }

        $collection = $query->whereIn($idColumn ?? 'id' ?: $query->getModel()->getKeyName(), $data->ids)
            ->get();

        return new ActionCollectionData($collection, $data->data);
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    protected function checkModels(CollectionActionData $collectionActionData, User $user, Builder $query, string $policyMethod, ?array $rules = null): CollectionActionCheckData
    {
        $actionCollectionData = $this->mapIdsToCollection($collectionActionData, $query);

        $actionCheckData = new CollectionActionCheckData(
            new Collection,
            new Collection,
        );

        foreach ($actionCollectionData->models as $model) {
            $modelActionCheckData = $this->checkModel($model, $user, $policyMethod, $rules);

            if ($modelActionCheckData->eligible === true) {
                $actionCheckData->eligible->push($modelActionCheckData->model);

                continue;
            }

            $actionCheckData->ineligible->push($modelActionCheckData->model);
        }

        return $actionCheckData;
    }
}
