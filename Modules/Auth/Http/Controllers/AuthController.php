<?php

namespace Modules\Auth\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Auth\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Auth\Http\Requests\RegisterRequest;

class AuthController extends Controller
{
    use ApiResponse;

    // membuat user baru untuk pengguna aplikasi
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('member');

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->apiSuccess([
            'user' => $user,
            'token' => $token,
            'role' => $user->getRoleNames()->first()
        ], 'Registrasi berhasil', 201);
    }

    // login user
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->apiError('Email atau password salah.', 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->apiSuccess([
            'user' => $user,
            'token' => $token,
            'role' => $user->getRoleNames()->first()
        ], 'Login berhasil');
    }

    // Logout user
    public function logout(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $accessToken = $user->currentAccessToken();

        if ($accessToken instanceof PersonalAccessToken) {
            $accessToken->delete();
        }

        return $this->apiSuccess(null, 'Logout berhasil');
    }

    // mengambil data user yang sedang login
    public function me(Request $request)
    {
        return $this->apiSuccess(Auth::user(), 'Profile User');
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
}
