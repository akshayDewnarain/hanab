<?php

namespace Domain\Support\Transfer\Actions;

use Domain\Support\Actions\BaseAction;
use Domain\Support\Transfer\TransferCatalog;

class ListTransferModelsAction extends BaseAction
{
    /**
     * @return list<array{key: string}>
     */
    public function execute(): array
    {
        return array_map(
            fn ($model) => ['key' => $model->key, ...$model->profile->describe()],
            array_values((new TransferCatalog)->all()),
        );
    }
}
