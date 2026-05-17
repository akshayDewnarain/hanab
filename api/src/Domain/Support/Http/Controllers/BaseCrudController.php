<?php

namespace Domain\Support\Http\Controllers;

use Domain\Support\Actions\BaseAction;
use Domain\Support\DataTransferObjects\BaseData;
use Domain\Support\Enums\AppScopePrefix;
use Domain\Support\Helpers\ApiResponse;
use Domain\Support\Helpers\BaseHelper;
use Domain\Support\Http\Requests\QueryBuilders\BaseModelQueryRequest;
use Domain\Support\Http\Requests\QueryBuilders\BaseQueryBuilder;
use Domain\Support\Http\Resources\BaseResource;
use Domain\Support\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class BaseCrudController extends BaseController
{
//    public function __construct()
//    {
//        $this->authorizeResource($this->model(), $this->routeModelParameter());
//    }

    /**
     * Override this function to show a human-readable name for the model in feedback messages.
     */
    abstract protected function modelName(): string;

    /**
     * This should return the route parameter required for authorizing the resource through a policy.
     */
    abstract protected function routeModelParameter(): string;

    /**
     * This should return the Eloquent model that's being controlled.
     *
     * @return BaseModel|class-string<BaseModel>
     */
    abstract protected function model(): mixed;

    /**
     * This should return the model's related map
     *
     * @return BaseHelper|class-string<BaseHelper>
     */
    abstract protected function helper(): mixed;

    /**
     * This should return the data transfer object which is passed on to actions.
     *
     * @return BaseData|class-string<BaseData>
     */
    abstract protected function dto(): mixed;

    /**
     * This should return the resource class for the model.
     *
     * @return BaseResource|class-string<BaseResource>
     */
    abstract protected function resource(): mixed;

    /**
     * This should return the resourceCollection class for the model.
     *
     * @return ResourceCollection|class-string<ResourceCollection>
     */
    abstract protected function resourceCollection(): mixed;

    /**
     * This should return the store action which is used in the store function/endpoint.
     */
    abstract protected function createActionClass(): string;

    /**
     * This should return the update action which is used in the update function/endpoint.
     */
    abstract protected function updateActionClass(): string;

    /**
     * This should return the delete action which is used in the delete function/endpoint.
     */
    abstract protected function deleteActionClass(): string;

    /**
     * This should return the API it's scope .
     */
    abstract protected function appScopePrefix(): AppScopePrefix;

    /**
     * This should return the query builder class for the index function of the controller.
     *
     * @return BaseQueryBuilder|class-string<BaseQueryBuilder>
     */
    abstract protected function queryClass(): mixed;

    /**
     * Dynamically handle the index request for the controller.
     */
    protected function handleIndex(BaseModelQueryRequest|Builder|Relation|string $reference, array $relationsToLoad = []): JsonResponse
    {
        try {
            $models = $reference instanceof BaseModelQueryRequest
                ? $reference->queryBuilder()
                    ->retrieve()
                : $this->queryBuilder($reference)
                    ->with([
                        ...$relationsToLoad,
                        ...$this->relationsToAlwaysLoad(),
                    ])
                    ->allowedFilters($this->helper()::filterable())
                    ->allowedSorts($this->helper()::sortable())
                    ->defaultSort($this->defaultIndexSort())
                    ->allowedIncludes($this->helper()::relations())
                    ->search($this->helper()::searchable())
                    ->retrieve();

            return $this->resourceCollection()::make($models)
                ->response();
        } catch (Throwable $e) {
            Log::error($e);

            return ApiResponse::error(__("{$this->appScopePrefix()->format()}_RESPONSE_MODEL_INDEX_ERROR", [
                'model' => $this->modelName(),
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * Dynamically handle the show request for the controller.
     */
    protected function handleShow(Model $model, array $relationsToLoad = []): JsonResponse
    {
        try {
            $model->load([
                ...$relationsToLoad,
                ...$this->relationsToAlwaysLoad(),
                ...$this->helper()::relationsToLoad(),
                ...$this->helper()::relations(),
            ]);

            return $this->resource()::make($model)
                ->response();
        } catch (Throwable $e) {
            Log::error($e);

            return ApiResponse::error(__("{$this->appScopePrefix()->format()}_RESPONSE_MODEL_SHOW_ERROR", [
                'model' => $this->modelName(),
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * Dynamically handle the store request for the controller.
     */
    protected function handleStore(FormRequest $request, array $relationsToLoad = []): JsonResponse
    {
        try {
            $model = $this->createAction()->execute($this->dto()::from($request->validated()));

            $model->load([
                ...$relationsToLoad,
                ...$this->relationsToAlwaysLoad(),
                ...$this->helper()::relationsToLoad(),
            ]);

            return $this->resource()::make($model)
                ->response();
        } catch (Throwable $e) {
            Log::error($e);

            return ApiResponse::error(__("{$this->appScopePrefix()->format()}_RESPONSE_MODEL_STORE_ERROR", [
                'model' => $this->modelName(),
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * Dynamically handle the update request for the controller.
     */
    protected function handleUpdate(FormRequest $request, Model $model, array $relationsToLoad = []): JsonResponse
    {
        try {
            $model = $this->updateAction()->execute($model, $this->dto()::fromUpdateRequest($model, $request));

            $model->load([
                ...$relationsToLoad,
                ...$this->relationsToAlwaysLoad(),
                ...$this->helper()::relationsToLoad(),
            ]);

            return $this->resource()::make($model)
                ->response();
        } catch (Throwable $e) {
            Log::error($e);

            return ApiResponse::error(__("{$this->appScopePrefix()->format()}_RESPONSE_MODEL_UPDATE_ERROR", [
                'model' => $this->modelName(),
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * Dynamically handle the destroy request for the controller.
     */
    protected function handleDestroy(Model $model): JsonResponse
    {
        try {
            $this->deleteAction()
                ->execute($model);

            return ApiResponse::deleted(__("{$this->appScopePrefix()->format()}_RESPONSE_MODEL_DESTROY_SUCCESS", [
                'model' => $this->modelName(),
            ]));
        } catch (Throwable $e) {
            Log::error($e);

            return ApiResponse::error(__("{$this->appScopePrefix()->format()}_RESPONSE_MODEL_DESTROY_ERROR", [
                'model' => $this->modelName(),
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * Returns the action class responsible for handling the store operation.
     */
    protected function createAction(): BaseAction
    {
        return app($this->createActionClass());
    }

    /**
     * Return the action class responsible for handling the update operation.
     */
    protected function updateAction(): BaseAction
    {
        return app($this->updateActionClass());
    }

    /**
     * Return the action class responsible for handling the delete operation.
     */
    protected function deleteAction(): BaseAction
    {
        return app($this->deleteActionClass());
    }

    /**
     * Override this functions to enforce the default sorting on the index page
     */
    protected function defaultIndexSort(): string
    {
        return '-id';
    }

    /**
     * Override this functions to enforce the relations that will always be loaded
     *
     * @return array<int, string>
     */
    protected function relationsToAlwaysLoad(): array
    {
        return [];
    }

    /**
     * Create a new query builder instance for the given reference.
     */
    protected function queryBuilder(Builder|Relation|string $reference): BaseQueryBuilder
    {
        return $this->queryClass()::for($reference);
    }
}
