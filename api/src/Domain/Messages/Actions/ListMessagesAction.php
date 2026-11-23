<?php

namespace Domain\Messages\Actions;

use Domain\Auth\Models\User;
use Domain\Messages\Models\Message;
use Domain\Messages\Models\MessagePreference;
use Domain\Support\Actions\BaseAction;
use Illuminate\Support\Collection;

class ListMessagesAction extends BaseAction
{
    /**
     * @return array{messages: Collection<int, Message>, drafts: Collection<int, Message>, can_publish: bool, can_create: bool}
     */
    public function execute(User $user, bool $canPublish, bool $canCreate, bool $ignorePreferences = false): array
    {
        EnsureDueMessagesAction::make()->execute();

        $preference = MessagePreference::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['people' => true, 'milestones' => true, 'company' => true, 'push_notifications' => true],
        );

        $categories = [Message::CATEGORY_COMPANY, Message::CATEGORY_PEOPLE, Message::CATEGORY_MILESTONES];
        if (! $ignorePreferences) {
            $categories = [Message::CATEGORY_COMPANY];
            if ($preference->people) {
                $categories[] = Message::CATEGORY_PEOPLE;
            }
            if ($preference->milestones) {
                $categories[] = Message::CATEGORY_MILESTONES;
            }
        }

        $messages = Message::query()
            ->with(['reactions', 'author'])
            ->where('status', Message::STATUS_PUBLISHED)
            ->whereIn('category', $categories)
            ->when(! $preference->push_notifications, function ($query): void {
                $query->whereNotIn('type', [Message::TYPE_BIRTHDAY, Message::TYPE_ANNIVERSARY]);
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $draftQuery = Message::query()
            ->with(['reactions', 'author'])
            ->where('status', Message::STATUS_DRAFT)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(100);

        if ($canPublish) {
            $drafts = $draftQuery->get();
        } elseif ($canCreate) {
            $drafts = $draftQuery
                ->where('type', Message::TYPE_CONTRACT)
                ->where('author_id', $user->id)
                ->get();
        } else {
            $drafts = new Collection;
        }

        return [
            'messages' => $messages,
            'drafts' => $drafts,
            'can_publish' => $canPublish,
            'can_create' => $canCreate,
        ];
    }
}
