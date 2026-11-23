<?php

namespace Domain\Messages\Actions;

use Domain\Auth\Models\User;
use Domain\Messages\Models\Message;
use Domain\Support\Actions\BaseAction;

class CreateContractMessageAction extends BaseAction
{
    public function execute(User $user, string $title, string $body, string $status = Message::STATUS_PUBLISHED): Message
    {
        $published = $status === Message::STATUS_PUBLISHED;

        return RecordMessageAction::make()->execute([
            'type' => Message::TYPE_CONTRACT,
            'category' => Message::CATEGORY_COMPANY,
            'status' => $published ? Message::STATUS_PUBLISHED : Message::STATUS_DRAFT,
            'title' => $title,
            'body' => $body,
            'author_id' => $user->id,
            'published_at' => $published ? now() : null,
        ])->load(['reactions', 'author']);
    }
}
