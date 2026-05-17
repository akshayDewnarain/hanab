<?php

namespace Domain\Support\Actions;

use Domain\Support\DataTransferObjects\ActionResult;
use Domain\Support\DataTransferObjects\CollectionActionData;

abstract class BaseCollectionAction extends BaseAction
{
    abstract public function execute(CollectionActionData $data): ActionResult;
}
