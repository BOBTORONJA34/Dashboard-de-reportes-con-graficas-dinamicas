<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            \Database\Seeders\CategorySeeder::class,
            \Database\Seeders\RegionSeeder::class,
        CategorySeeder::class,
        RegionSeeder::class,
        DemoSeeder::class,
        AdminSeeder::class,   // 👈 Asegúrate de incluirlo
        ]);

        // ❌ Sin ventas demo. Quedan solo cat/region.
        // \App\Models\Sale::factory()->count(120)->create();
    }
}
