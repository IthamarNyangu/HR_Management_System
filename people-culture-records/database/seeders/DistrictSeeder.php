<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Province;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    /**
     * @var array<string, array<int, array{name: string, code: string}>>
     */
    private array $districts = [
        'Luapula' => [
            ['name' => 'Mansa', 'code' => 'LUA-MAN'],
            ['name' => 'Chembe', 'code' => 'LUA-CHE'],
            ['name' => 'Chienge', 'code' => 'LUA-CHG'],
            ['name' => 'Chifunabuli', 'code' => 'LUA-CHF'],
            ['name' => 'Chipili', 'code' => 'LUA-CHP'],
            ['name' => 'Kawambwa', 'code' => 'LUA-KAW'],
            ['name' => 'Lunga', 'code' => 'LUA-LUN'],
            ['name' => 'Milenge', 'code' => 'LUA-MIL'],
            ['name' => 'Mwansabombwe', 'code' => 'LUA-MSB'],
            ['name' => 'Mwense', 'code' => 'LUA-MWE'],
            ['name' => 'Nchelenge', 'code' => 'LUA-NCH'],
            ['name' => 'Samfya', 'code' => 'LUA-SAM'],
        ],
        'Muchinga' => [
            ['name' => 'Chama', 'code' => 'MUC-CHA'],
            ['name' => 'Chinsali', 'code' => 'MUC-CHS'],
            ['name' => 'Isoka', 'code' => 'MUC-ISO'],
            ['name' => 'Mafinga', 'code' => 'MUC-MAF'],
            ['name' => 'Mpika', 'code' => 'MUC-MPI'],
            ['name' => 'Nakonde', 'code' => 'MUC-NAK'],
            ['name' => 'Shiwang’andu', 'code' => 'MUC-SHI'],
            ['name' => 'Kanchibiya', 'code' => 'MUC-KAN'],
            ['name' => 'Lavushimanda', 'code' => 'MUC-LAV'],
        ],
        'Northern' => [
            ['name' => 'Kasama', 'code' => 'NOR-KAS'],
            ['name' => 'Chilubi', 'code' => 'NOR-CHI'],
            ['name' => 'Kaputa', 'code' => 'NOR-KAP'],
            ['name' => 'Luwingu', 'code' => 'NOR-LUW'],
            ['name' => 'Lunte', 'code' => 'NOR-LUN'],
            ['name' => 'Lupososhi', 'code' => 'NOR-LUP'],
            ['name' => 'Mbala', 'code' => 'NOR-MBA'],
            ['name' => 'Mporokoso', 'code' => 'NOR-MPO'],
            ['name' => 'Mpulungu', 'code' => 'NOR-MPU'],
            ['name' => 'Mungwi', 'code' => 'NOR-MUN'],
            ['name' => 'Nsama', 'code' => 'NOR-NSA'],
            ['name' => 'Senga Hill', 'code' => 'NOR-SEN'],
        ],
        'Lusaka' => [
            ['name' => 'Lusaka', 'code' => 'LUS-LUS'],
        ],
    ];

    public function run(): void
    {
        foreach ($this->districts as $provinceName => $districts) {
            $province = Province::where('name', $provinceName)->firstOrFail();

            foreach ($districts as $district) {
                District::updateOrCreate(
                    [
                        'province_id' => $province->id,
                        'name' => $district['name'],
                    ],
                    [
                        'code' => $district['code'],
                        'description' => null,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
