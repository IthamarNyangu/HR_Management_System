<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Admin', 'code' => 'ADMIN', 'description' => 'Full system access.'],
            ['name' => 'HR Manager', 'code' => 'HRM', 'description' => 'Manage HR records and master data across provinces.'],
            ['name' => 'HR Officer', 'code' => 'HRO', 'description' => 'Manage records for an assigned province.'],
            ['name' => 'Viewer', 'code' => 'VIEWER', 'description' => 'Read-only access for an assigned province.'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role + ['is_active' => true],
            );
        }
    }
}
