<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Password default untuk semua akun: 'password'
        $password = Hash::make('password');

        // 1. SUPER ADMIN
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@app.com'],
            [
                'name' => 'Super Admin',
                'password' => $password,
            ]
        );
        $superAdmin->assignRole('super-admin');

        // 2. ADMIN (Pengelola)
        $admin = User::firstOrCreate(
            ['email' => 'admin@app.com'],
            [
                'name' => 'Admin Wisma',
                'password' => $password,
            ]
        );
        $admin->assignRole('admin');

        // 3. MEMBER (User Biasa / Calon Penghuni)
        $member = User::firstOrCreate(
            ['email' => 'member@app.com'],
            [
                'name' => 'Budi Member',
                'password' => $password,
            ]
        );
        $member->assignRole('member');

        // Bagian Resident dihapus sesuai permintaan agar tidak error
    }
}
