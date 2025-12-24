<?php

namespace Modules\Auth\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponse;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('guest');

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->apiSuccess([
            'user' => $user,
            'token' => $token,
            'role' => $user->getRoleNames()->first()
        ], 'Registrasi berhasil', 201);
    }

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

    public function me(Request $request)
    {
        return $this->apiSuccess(Auth::user(), 'Profile User');
    }


    public function myPermissions()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $permissions = $user->getAllPermissions()->pluck('name');

        return $this->apiSuccess($permissions, 'User permissions retrieved successfully');
    }
}
