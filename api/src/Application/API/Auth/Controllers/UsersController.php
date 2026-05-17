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
use Domain\Auth\Helpers\UserHelper;
use Domain\Auth\Models\User;
use Domain\Support\Helpers\ApiResponse;
use Domain\Support\Http\Controllers\BaseAPICrudController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;

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

    public function store(CreateUserRequest $request): JsonResponse
    {
        return $this->handleStore($request);
    }

    public function show(User $user): JsonResponse
    {
        return $this->handleShow($user);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        return $this->handleUpdate($request, $user);
    }

    public function destroy(User $user): JsonResponse
    {
        return $this->handleDestroy($user);
    }

    public function sendPasswordResetEmail(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        Cache::put('password_reset_admin:'.$user->email, true, now()->addMinutes(5));

        $status = Password::broker()->sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_THROTTLED) {
            return ApiResponse::error(__('Please wait before requesting another reset link.'), null, 429);
        }

        if ($status !== Password::RESET_LINK_SENT) {
            return ApiResponse::error(__('Could not send the password reset email. Please try again.'));
        }

        return ApiResponse::success(__('Password reset email sent.'));
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
