<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Models\User;
use Modules\Auth\Services\AuthService;

class AuthController extends Controller
{
    use ApiResponse;

    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Registrasi Pengguna
     *
     * Membuat akun baru untuk pengguna/calon penghuni.
     * @unauthenticated
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->authService->register($request->validated());

            return $this->apiSuccess($user, 'Registrasi berhasil', 201);
        } catch (\Exception $e) {
            return $this->apiError($e->getMessage(), 500);
        }
    }

    /**
     * Login Pengguna
     *
     * Melakukan autentikasi pengguna dan mengembalikan token Sanctum.
     * @unauthenticated
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Logout Pengguna
     *
     * Menghapus token autentikasi pengguna saat ini.
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return $this->apiSuccess(null, 'Logout berhasil');
    }

    /**
     * Data Pengguna Saat Ini
     *
     * Mengambil profil pengguna yang sedang terautentikasi.
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        return $this->apiSuccess($request->user(), 'Data user berhasil diambil');
    }

    /**
     * Hak Akses Pengguna
     *
     * Mengambil daftar peran (roles) dan izin (permissions) pengguna saat ini.
     * Jika tidak login, akan mengembalikan role guest.
     * @unauthenticated
     * @return \Illuminate\Http\JsonResponse
     */
    public function myPermissions(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user('sanctum');

        if (! $user) {
            $guestRole = \Spatie\Permission\Models\Role::where('name', 'guest')->where('guard_name', 'api')->first();
            $permissions = $guestRole ? $guestRole->permissions->pluck('name') : collect(['view-room']);

            return $this->apiSuccess([
                'permissions' => $permissions->values()->all(),
                'roles'       => ['guest'],
            ], 'Guest permissions retrieved successfully');
        }

        return $this->apiSuccess([
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
            'roles'       => $user->getRoleNames()->values()->all(),
        ], 'User permissions retrieved successfully');
    }

    /**
     * Update Profil
     *
     * Memperbarui nama dan email pengguna saat ini.
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
        ]);

        $user->update($validated);

        return $this->apiSuccess($user, 'Profil berhasil diperbarui');
    }

    /**
     * Ubah Password
     *
     * Memperbarui password pengguna saat ini.
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($request->old_password, $user->password)) {
            return $this->apiError('Password lama tidak sesuai', 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return $this->apiSuccess(null, 'Password berhasil diubah');
    }
}
