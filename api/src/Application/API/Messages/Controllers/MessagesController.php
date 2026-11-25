<?php

namespace Application\API\Messages\Controllers;

use Domain\Auth\Models\User;
use Domain\Auth\Services\PermissionService;
use Domain\Auth\Support\Maintainer;
use Domain\Messages\Actions\CreateContractMessageAction;
use Domain\Messages\Actions\DeleteDraftMessageAction;
use Domain\Messages\Actions\ListMessagesAction;
use Domain\Messages\Actions\PublishMessageAction;
use Domain\Messages\Actions\SetMessageReactionAction;
use Domain\Messages\Actions\UpdateContractMessageAction;
use Domain\Messages\Http\Requests\CreateContractMessageRequest;
use Domain\Messages\Http\Requests\MessagePreferenceRequest;
use Domain\Messages\Http\Requests\MessageReactionRequest;
use Domain\Messages\Http\Requests\UpdateContractMessageRequest;
use Domain\Messages\Http\Resources\MessageResource;
use Domain\Messages\Models\Message;
use Domain\Messages\Models\MessagePreference;
use Domain\Support\Helpers\ApiResponse;
use Domain\Support\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MessagesController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $canPublish = $this->can($user, 'publish messages');
        $canCreate = $this->can($user, PermissionService::createPermission(Message::class));
        $canUpdate = $this->can($user, PermissionService::updatePermission(Message::class));
        $canDelete = $this->can($user, PermissionService::deletePermission(Message::class));
        $manage = $request->query('scope') === 'manage' && ($canPublish || $canUpdate);
        $result = ListMessagesAction::make()->execute($user, $canPublish, $canCreate, $manage);

        return response()->json([
            'data' => $this->present($result['messages'], $user),
            'drafts' => $this->present($result['drafts'], $user),
            'can_publish' => $result['can_publish'],
            'can_create' => $result['can_create'],
            'can_update' => $canUpdate,
            'can_delete' => $canDelete,
        ]);
    }

    public function store(CreateContractMessageRequest $request): JsonResponse
    {
        $user = $this->user($request);
        abort_unless($this->can($user, PermissionService::createPermission(Message::class)), 403);

        $requested = $request->validated('status');
        $canPublish = $this->can($user, 'publish messages');
        if ($requested === Message::STATUS_PUBLISHED || ($requested === null && $canPublish)) {
            abort_unless($canPublish, 403);
            $status = Message::STATUS_PUBLISHED;
        } else {
            $status = Message::STATUS_DRAFT;
        }

        $message = CreateContractMessageAction::make()->execute(
            $user,
            $request->validated('title'),
            $request->validated('body'),
            $status,
        );

        return response()->json([
            'data' => (new MessageResource($message, $user))->toArray($request),
        ], 201);
    }

    public function update(UpdateContractMessageRequest $request, Message $message): JsonResponse
    {
        $user = $this->user($request);
        abort_unless($this->can($user, PermissionService::updatePermission(Message::class)), 403);

        $message = UpdateContractMessageAction::make()->execute(
            $message,
            $request->validated('title'),
            $request->validated('body'),
        );

        return response()->json([
            'data' => (new MessageResource($message, $user))->toArray($request),
        ]);
    }

    public function destroy(Request $request, Message $message): Response
    {
        $user = $this->user($request);
        abort_unless($this->can($user, PermissionService::deletePermission(Message::class)), 403);

        DeleteDraftMessageAction::make()->execute($message);

        return response()->noContent();
    }

    public function publish(Request $request, Message $message): JsonResponse
    {
        $user = $this->user($request);
        abort_unless($this->can($user, 'publish messages'), 403);

        $message = PublishMessageAction::make()->execute($message);

        return response()->json([
            'data' => (new MessageResource($message, $user))->toArray($request),
        ]);
    }

    public function reaction(MessageReactionRequest $request, Message $message): JsonResponse
    {
        $user = $this->user($request);
        abort_unless($message->status === Message::STATUS_PUBLISHED, 422);

        $message = SetMessageReactionAction::make()->execute($message, $user, $request->validated('emoji'));

        return response()->json([
            'data' => (new MessageResource($message, $user))->toArray($request),
        ]);
    }

    public function preferences(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $preference = MessagePreference::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['people' => true, 'milestones' => true, 'company' => true, 'push_notifications' => true],
        );

        return response()->json(['data' => $this->preferencePayload($preference)]);
    }

    public function updatePreferences(MessagePreferenceRequest $request): JsonResponse
    {
        $user = $this->user($request);
        if (! $request->boolean('company')) {
            return ApiResponse::error('Company announcements cannot be disabled.', null, 422);
        }

        $current = MessagePreference::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['people' => true, 'milestones' => true, 'company' => true, 'push_notifications' => true],
        );
        $current->update([
            'people' => $request->boolean('people'),
            'milestones' => $request->boolean('milestones'),
            'company' => true,
            'push_notifications' => $request->exists('push_notifications')
                ? $request->boolean('push_notifications')
                : $current->push_notifications,
        ]);
        $preference = $current->refresh();

        return response()->json(['data' => $this->preferencePayload($preference)]);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function can(User $user, string $permission): bool
    {
        return Maintainer::allows($user, $permission);
    }

    /**
     * @param  iterable<Message>  $messages
     * @return list<array<string, mixed>>
     */
    private function present(iterable $messages, User $user): array
    {
        $rows = [];
        foreach ($messages as $message) {
            $rows[] = (new MessageResource($message, $user))->toArray(request());
        }

        return $rows;
    }

    /**
     * @return array{people: bool, milestones: bool, company: bool, company_locked: bool, push_notifications: bool}
     */
    private function preferencePayload(MessagePreference $preference): array
    {
        return [
            'people' => $preference->people,
            'milestones' => $preference->milestones,
            'company' => true,
            'company_locked' => true,
            'push_notifications' => $preference->push_notifications,
        ];
    }
}
