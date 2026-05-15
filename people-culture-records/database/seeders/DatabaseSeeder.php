<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ProvinceSeeder::class,
            DistrictSeeder::class,
            MasterDataSeeder::class,
            OfficialJobTitleSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
