<?php

namespace Modules\Auth\database\seeders;

use Modules\Auth\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@app.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );
        $superAdmin->assignRole('super-admin');

        $admin = User::updateOrCreate(
            ['email' => 'admin@app.com'],
            [
                'name' => 'Admin Operasional',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('admin');

        for ($i = 1; $i <= 20; $i++) {
            $member = User::updateOrCreate(
                [
                    'email' => "user{$i}@app.com"
                ],
                [
                    'name' => "User {$i}",
                    'password' => Hash::make('password'),
                ]
            );
            $member->assignRole('member');
        }
    }
}
