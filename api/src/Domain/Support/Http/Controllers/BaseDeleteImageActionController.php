<?php

namespace Domain\Support\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spatie\MediaLibrary\HasMedia;

abstract class BaseDeleteImageActionController extends Controller
{
    //    use AuthorizesRequests;

    /** Name of the route model binding param, e.g. 'drink' */
    protected string $param = 'model';

    /** Media collection to clear */
    protected string $collection = 'image';

    public function __invoke(Request $request)
    {
        /** @var class-string<Model&HasMedia> $class */
        $class = $this->modelClass();

        /** @var Model&HasMedia $model */
        $model = $request->route($this->param);
        if (! $model instanceof $class) {
            $id = $request->route($this->param);
            $model = $class::query()->findOrFail($id);
        }

        //        $this->authorize('update', $model);

        $model->clearMediaCollection($this->collection());

        return response()->noContent();
    }

    /** Must return the FQCN of the model we’re operating on */
    abstract protected function modelClass(): string;

    /** Optional: override per-model */
    protected function collection(): string
    {
        return $this->collection;
    }
}
