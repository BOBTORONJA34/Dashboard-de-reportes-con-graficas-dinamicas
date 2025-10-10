<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'Electrónicos'],
            ['name' => 'Ropa'],
            ['name' => 'Hogar'],
            ['name' => 'Alimentos'],
        ];
        // upsert evita duplicados si se re-ejecuta
        Category::upsert($data, ['name'], []);
    }
}
