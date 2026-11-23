<?php

namespace Application\API\Auth\Controllers;

use Application\API\Auth\Resources\UserInvitationResource;
use Domain\Auth\Exceptions\InvitationDeliveryException;
use Domain\Auth\Models\UserInvitation;
use Domain\Auth\Support\UserInvitationService;
use Domain\Support\Helpers\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class UserInvitationsController
{
    use AuthorizesRequests;

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', UserInvitation::class);

        $invitations = UserInvitation::query()
            ->whereHas('invitedUser', fn ($query) => $query->clientVisible())
            ->with(['invitedUser', 'role'])
            ->latest('id')
            ->limit(100)
            ->get();

        return UserInvitationResource::collection($invitations)->response();
    }

    public function show(UserInvitation $userInvitation): JsonResponse
    {
        $this->authorize('view', $userInvitation);

        return (new UserInvitationResource($userInvitation->load(['invitedUser', 'role'])))->response();
    }

    public function resend(UserInvitation $userInvitation, UserInvitationService $invitations): JsonResponse
    {
        $this->authorize('update', $userInvitation);

        try {
            $invitation = $invitations->resend($userInvitation, request()->user());
        } catch (InvitationDeliveryException $exception) {
            return ApiResponse::error($exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage());
        }

        return (new UserInvitationResource($invitation))->response();
    }

    public function revoke(UserInvitation $userInvitation, UserInvitationService $invitations): JsonResponse
    {
        $this->authorize('update', $userInvitation);

        try {
            $invitation = $invitations->revoke($userInvitation);
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage());
        }

        return (new UserInvitationResource($invitation))->response();
    }
}
