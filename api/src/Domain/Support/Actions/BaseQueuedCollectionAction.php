<?php

namespace Domain\Support\Actions;

use Spatie\QueueableAction\QueueableAction;

abstract class BaseQueuedCollectionAction extends BaseCollectionAction
{
    use QueueableAction;
}
