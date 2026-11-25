<?php

namespace Domain\Dashboard\Actions;

use Carbon\Carbon;
use Domain\Auth\Models\User;
use Domain\Messages\Models\Message;
use Domain\Messages\Models\MessagePreference;
use Domain\Support\Actions\BaseAction;
use Illuminate\Database\Eloquent\Builder;

class ListDashboardActivityAction extends BaseAction
{
    /**
     * Published messages the viewer is allowed to see. Drafts stay out.
     * Older pages use a cursor. Newer pages use the same cursor of the newest row the client already has.
     *
     * @return array{data: list<array<string, mixed>>, meta: array{has_more: bool, next_cursor: ?string, newest_cursor: ?string}}
     */
    public function execute(User $user, int $limit = 8, ?string $cursor = null, ?string $after = null): array
    {
        $limit = max(1, min(20, $limit));
        $preference = MessagePreference::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['people' => true, 'milestones' => true, 'company' => true, 'push_notifications' => true],
        );

        $categories = [Message::CATEGORY_COMPANY];
        if ($preference->people) {
            $categories[] = Message::CATEGORY_PEOPLE;
        }
        if ($preference->milestones) {
            $categories[] = Message::CATEGORY_MILESTONES;
        }

        $query = Message::query()
            ->with(['subject:id,is_active'])
            ->where('status', Message::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->whereIn('category', $categories)
            ->where(function (Builder $visible): void {
                $visible->whereNull('subject_employee_id')
                    ->orWhereHas('subject', function (Builder $subject): void {
                        $subject->where('is_active', true);
                    });
            })
            ->when(! $preference->push_notifications, function (Builder $query): void {
                $query->whereNotIn('type', [Message::TYPE_BIRTHDAY, Message::TYPE_ANNIVERSARY]);
            });

        $older = $this->decodeCursor($cursor);
        $newer = $this->decodeCursor($after);
        if ($newer !== null) {
            $query->where(function (Builder $query) use ($newer): void {
                $query->where('published_at', '>', $newer['published_at'])
                    ->orWhere(function (Builder $query) use ($newer): void {
                        $query->where('published_at', $newer['published_at'])->where('id', '>', $newer['id']);
                    });
            });
        } elseif ($older !== null) {
            $query->where(function (Builder $query) use ($older): void {
                $query->where('published_at', '<', $older['published_at'])
                    ->orWhere(function (Builder $query) use ($older): void {
                        $query->where('published_at', $older['published_at'])->where('id', '<', $older['id']);
                    });
            });
        }

        $messages = $query
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $messages->count() > $limit;
        $page = $messages->take($limit);
        $items = [];
        foreach ($page as $message) {
            $items[] = [
                'id' => 'message:'.$message->id,
                'type' => $message->type,
                'category' => $message->category,
                'title' => $message->title,
                'body' => $message->body,
                'subject_name' => $message->subject_name,
                'detail' => $message->detail,
                'years' => $message->years,
                'subject_employee_id' => $message->subject_employee_id,
                'published_at' => $message->published_at?->toIso8601String(),
            ];
        }

        $last = $page->last();
        $first = $page->first();

        return [
            'data' => $items,
            'meta' => [
                'has_more' => $newer !== null ? false : $hasMore,
                'next_cursor' => $newer !== null || ! $hasMore || $last === null ? null : $this->encodeCursor($last),
                'newest_cursor' => $first === null ? null : $this->encodeCursor($first),
            ],
        ];
    }

    /**
     * @return array{published_at: Carbon, id: int}|null
     */
    private function decodeCursor(?string $cursor): ?array
    {
        if ($cursor === null || $cursor === '') {
            return null;
        }

        $json = base64_decode(strtr($cursor, '-_', '+/'), true);
        if ($json === false) {
            return null;
        }

        $data = json_decode($json, true);
        if (! is_array($data) || ! isset($data['published_at'], $data['id'])) {
            return null;
        }

        try {
            $publishedAt = Carbon::parse((string) $data['published_at']);
        } catch (\Throwable) {
            return null;
        }

        return [
            'published_at' => $publishedAt,
            'id' => (int) $data['id'],
        ];
    }

    private function encodeCursor(Message $message): ?string
    {
        if ($message->published_at === null) {
            return null;
        }

        $json = json_encode([
            'published_at' => $message->published_at->toIso8601String(),
            'id' => $message->id,
        ]);
        if ($json === false) {
            return null;
        }

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }
}
