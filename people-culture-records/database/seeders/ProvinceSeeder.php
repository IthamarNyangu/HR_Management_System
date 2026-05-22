<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Northern', 'Luapula', 'Muchinga', 'Lusaka'] as $province) {
            Province::updateOrCreate(
                ['name' => $province],
                [
                    'code' => strtoupper(substr($province, 0, 3)),
                    'description' => null,
                    'is_active' => true,
                ],
            );
        }
    }
}
