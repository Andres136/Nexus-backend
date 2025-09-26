<?php

namespace Database\Seeders;

use App\Models\Crm\categoria;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        categoria::create([
            'nombre' => 'General',
            'descripcion' => 'Categoría general para productos diversos',
            
        ]);
    }
}
