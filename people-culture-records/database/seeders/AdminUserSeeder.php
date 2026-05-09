<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'Admin')->firstOrFail();

        User::updateOrCreate(
            ['email' => env('DEFAULT_ADMIN_EMAIL', 'admin@righttocare.test')],
            [
                'name' => env('DEFAULT_ADMIN_NAME', 'System Administrator'),
                'password' => Hash::make(env('DEFAULT_ADMIN_PASSWORD', 'Password@123')),
                'role_id' => $adminRole->id,
                'province_id' => null,
                'is_active' => true,
            ],
        );
    }
}
