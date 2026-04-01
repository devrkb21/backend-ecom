<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $users = $this->userService->getPaginatedUsers($this->perPage());

        return $this->successResponse(UserResource::collection($users)->response()->getData(true));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);

        // Users can only view their own profile unless admin
        if (!$request->user()->isAdmin() && $request->user()->id !== $id) {
            return $this->errorResponse('Unauthorized', 403);
        }

        return $this->successResponse(new UserResource($user));
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        // Users can only update their own profile unless admin
        if (!$request->user()->isAdmin() && $request->user()->id !== $id) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $user = $this->userService->updateUser($id, $request->validated());

        return $this->successResponse(new UserResource($user), 'User updated successfully');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $this->userService->deleteUser($id);

        return $this->successResponse(null, 'User deleted successfully');
    }

    public function profile(Request $request): JsonResponse
    {
        return $this->successResponse(new UserResource($request->user()));
    }

    public function updateProfile(UpdateUserRequest $request): JsonResponse
    {
        $user = $this->userService->updateUser($request->user()->id, $request->validated());

        return $this->successResponse(new UserResource($user), 'Profile updated successfully');
    }

    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['nullable', 'string', 'in:' . implode(',', array_keys(User::roleOptions()))],
        ]);

        $user = $this->userService->createUser($request->only(['name', 'email', 'password', 'phone', 'role']));

        return $this->createdResponse(new UserResource($user), 'User created successfully');
    }

    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        if ($request->user()->id === $id) {
            return $this->errorResponse('Cannot change your own status.', 400);
        }

        $user = $this->userService->toggleUserStatus($id);
        $status = $user->deleted_at ? 'deactivated' : 'activated';

        return $this->successResponse(new UserResource($user), "User {$status} successfully");
    }
}
