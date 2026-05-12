<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Http\Requests\StoreUserRequest;
use Modules\Auth\Http\Requests\UpdateUserRequest;
use Modules\Auth\Services\UserService;
use Modules\Auth\Transformers\UserResource;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly UserService $userService
    ) {}

    public function index(): JsonResponse
    {
        $users = $this->userService->getAllUsers();
        return $this->apiSuccess(UserResource::collection($users), 'Daftar pengguna berhasil diambil');
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());
        return $this->apiSuccess(new UserResource($user), "Berhasil membuat pengguna baru", 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->getUserDetails($id);
        return $this->apiSuccess(new UserResource($user), "Data detail pengguna berhasil diambil");
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->updateUser($id, $request->validated(), $request->role);
        return $this->apiSuccess(new UserResource($user), "Berhasil memperbarui data pengguna");
    }

    public function destroy(int $id): JsonResponse
    {
        $this->userService->deleteUser($id, Auth::id());
        return $this->apiSuccess(null, "Berhasil menghapus pengguna");
    }
}
