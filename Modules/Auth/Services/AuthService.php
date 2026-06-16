<?php

namespace Modules\Auth\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;

class AuthService
{
    public function register(array $data)
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone_number' => $data['phone_number'] ?? null,
            ]);

            $user->assignRole('member');

            $token = $user->createToken('auth_token')->plainTextToken;

            // return [
            //     'user' => $user,
            //     'token' => $token,
            //     'role' => 'member',
            // ];
            return [
                'user' => $user->load('roles'),
                'token' => $token,
                'role' => 'member',
            ];
        });
    }

    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new Exception('Kredensial tidak valid', 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'role' => $user->getRoleNames()->first(),
        ];
    }

    public function logout($user): void
    {
        if ($user) {
            $user->currentAccessToken()->delete();
        }
    }
}
