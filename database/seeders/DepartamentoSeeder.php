<?php

namespace Database\Seeders;

use App\Models\Departamentos;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartamentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Departamentos::create([
            'nombre' => 'Tecnología',
            'descripcion' => 'Departamento de tecnología',
             'macroprocesos_id' => 3,
             'icono' => 'iconos/logo.png',
        ]);


    }
}
