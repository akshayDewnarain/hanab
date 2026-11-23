<?php

namespace Domain\Messages\Actions;

use Domain\Auth\Models\User;
use Domain\Messages\Models\Message;
use Domain\Messages\Models\MessageReaction;
use Domain\Support\Actions\BaseAction;

class SetMessageReactionAction extends BaseAction
{
    public function execute(Message $message, User $user, ?string $emoji): Message
    {
        if ($emoji === null || $emoji === '') {
            MessageReaction::query()
                ->where('message_id', $message->id)
                ->where('user_id', $user->id)
                ->delete();
        } else {
            MessageReaction::query()->updateOrCreate(
                ['message_id' => $message->id, 'user_id' => $user->id],
                ['emoji' => $emoji],
            );
        }

        return $message->load('reactions');
    }
}
