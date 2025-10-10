<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Region;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'Norte'],
            ['name' => 'Centro'],
            ['name' => 'Sur'],
            ['name' => 'Occidente'],
        ];
        Region::upsert($data, ['name'], []);
    }
}
