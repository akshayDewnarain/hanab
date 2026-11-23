<?php

namespace Domain\Messages\Actions;

use Domain\Auth\Models\User;
use Domain\Messages\Models\Message;
use Domain\Support\Actions\BaseAction;

class PublishMessageAction extends BaseAction
{
    public function execute(Message $message): Message
    {
        if ($message->status !== Message::STATUS_PUBLISHED) {
            $message->status = Message::STATUS_PUBLISHED;
            $message->published_at = now();
            $message->save();
        }

        return $message->load(['reactions', 'author']);
    }
}
