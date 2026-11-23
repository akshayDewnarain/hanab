<?php

namespace Application\API\Auth\Controllers;

use Application\API\Auth\Requests\CreateUserRequest;
use Application\API\Auth\Requests\QueryBuilder\UserQueryRequest;
use Application\API\Auth\Requests\UpdateUserRequest;
use Application\API\Auth\Resources\UserResource;
use Application\API\Auth\Resources\UserResourceCollection;
use Domain\Auth\Actions\CreateUserAction;
use Domain\Auth\Actions\DeleteUserAction;
use Domain\Auth\Actions\UpdateUserAction;
use Domain\Auth\DataTransferObjects\UserData;
use Domain\Auth\Exceptions\InvitationDeliveryException;
use Domain\Auth\Exceptions\LastSuperAdminException;
use Domain\Auth\Helpers\UserHelper;
use Domain\Auth\Models\Role;
use Domain\Auth\Models\User;
use Domain\Auth\Support\BootstrapRoleNames;
use Domain\Auth\Support\SuperAdminGuard;
use Domain\Auth\Support\UserInvitationService;
use Domain\Support\Helpers\ApiResponse;
use Domain\Support\Http\Controllers\BaseAPICrudController;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class UsersController extends BaseAPICrudController
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function index(UserQueryRequest $request): JsonResponse
    {
        return $this->handleIndex($request);
    }

    public function store(CreateUserRequest $request, UserInvitationService $invitations): JsonResponse
    {
        $authUser = $request->user();
        $role = Role::query()->findOrFail($request->integer('role_id'));
        $allowSuperAdmin = $authUser instanceof User && $authUser->hasRole(BootstrapRoleNames::SUPER_ADMIN);

        try {
            $invitation = $invitations->invite(
                inviter: $authUser instanceof User ? $authUser : null,
                email: (string) $request->validated('email'),
                name: $request->validated('name'),
                roleId: $role->id,
                employeeId: $request->validated('employee_id'),
                allowSuperAdminRole: $allowSuperAdmin,
            );
        } catch (InvitationDeliveryException $exception) {
            return ApiResponse::error($exception->getMessage().' The account was created. Send the invitation again when mail is working.');
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage());
        }

        return (new UserResource($invitation->invitedUser->load('roles')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user): JsonResponse
    {
        return $this->handleShow($user);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        if ($request->input('status') === 'disabled' && SuperAdminGuard::isLastActive($user)) {
            return ApiResponse::error('The last active administrator cannot be removed or disabled.');
        }

        if ($request->filled('role_id')) {
            $role = Role::query()->find($request->integer('role_id'));
            $removesSuperAdmin = $role !== null
                && $user->hasRole(BootstrapRoleNames::SUPER_ADMIN)
                && $role->name !== BootstrapRoleNames::SUPER_ADMIN;
            if ($removesSuperAdmin && SuperAdminGuard::isLastActive($user)) {
                return ApiResponse::error('The last active administrator cannot be removed or disabled.');
            }
        }

        try {
            return $this->handleUpdate($request, $user);
        } catch (LastSuperAdminException $exception) {
            return ApiResponse::error($exception->getMessage());
        }
    }

    public function destroy(User $user): JsonResponse
    {
        if (SuperAdminGuard::isLastActive($user)) {
            return ApiResponse::error('The last active administrator cannot be removed or disabled.');
        }

        return $this->handleDestroy($user);
    }

    public function sendInvitation(User $user, UserInvitationService $invitations): JsonResponse
    {
        $this->authorize('update', $user);

        try {
            $actor = request()->user();
            $invitations->resendForUser($user, $actor instanceof User ? $actor : null);
        } catch (InvitationDeliveryException|InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage());
        }

        return ApiResponse::success('Invitation sent.');
    }

    public function revokeInvitation(User $user, UserInvitationService $invitations): JsonResponse
    {
        $this->authorize('update', $user);

        try {
            $invitations->revokeForUser($user);
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage());
        }

        return ApiResponse::success('Invitation revoked.');
    }

    protected function relationsToAlwaysLoad(): array
    {
        return ['roles'];
    }

    protected function modelName(): string
    {
        return 'users';
    }

    protected function routeModelParameter(): string
    {
        return 'user';
    }

    protected function model(): mixed
    {
        return User::class;
    }

    protected function helper(): mixed
    {
        return UserHelper::class;
    }

    protected function dto(): mixed
    {
        return UserData::class;
    }

    protected function resource(): mixed
    {
        return UserResource::class;
    }

    protected function resourceCollection(): mixed
    {
        return UserResourceCollection::class;
    }

    protected function createActionClass(): string
    {
        return CreateUserAction::class;
    }

    protected function updateActionClass(): string
    {
        return UpdateUserAction::class;
    }

    protected function deleteActionClass(): string
    {
        return DeleteUserAction::class;
    }
}
