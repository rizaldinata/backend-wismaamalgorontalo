<?php

namespace Modules\Auth\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Auth\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Services\AuthService;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Auth\Http\Requests\LoginRequest;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Http\Requests\RegisterRequest;

class AuthController extends Controller
{
    use ApiResponse;

    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    // membuat user baru untuk pengguna aplikasi
    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->authService->register($request->validated());

            return $this->apiSuccess($user, 'Registrasi berhasil', 201);
        } catch (\Exception $e) {
            return $this->apiError($e->getMessage(), 500);
        }
    }

    // login user
    public function login(LoginRequest $request)
    {
        try {
            $data = $this->authService->login(
                $request->email,
                $request->password
            );

            return $this->apiSuccess($data, 'Login berhasil');
        } catch (ValidationException $e) {
            return $this->apiError($e->getMessage(), 401);
        } catch (\Exception $e) {
            return $this->apiError('Terjadi kesalahan sistem', 500);
        }
    }

    // Logout user
    public function logout(Request $request)
    {
        $this->authService->logout($request->user());
        return $this->apiSuccess(null, 'Logout berhasil');
    }

    // mengambil data user yang sedang login
    public function me(Request $request)
    {
        return $this->apiSuccess($request->user(), 'Data user berhasil diambil');
    }

    // daftar permission use yang sedang login
    public function myPermissions(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user('sanctum');

        if (!$user) {
            $guestRole = \Spatie\Permission\Models\Role::where('name', 'guest')->where('guard_name', 'api')->first();
            $permissions = $guestRole ? $guestRole->permissions->pluck('name') : ['view-room'];
            return $this->apiSuccess($permissions, 'Guest permissions retrieved successfully');
        }

        $permissions = $user->getAllPermissions()->pluck('name');

        return $this->apiSuccess($permissions, 'User permissions retrieved successfully');
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return $this->apiSuccess($user, 'Profil berhasil diperbarui');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->old_password, $user->password)) {
            return $this->apiError('Password lama tidak sesuai', 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return $this->apiSuccess(null, 'Password berhasil diubah');
    }
}
