<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EstadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estados = [
            'Pendiente',
            'Completado',
            'Activo',
            'Inactivo'
        ];

        foreach ($estados as $estado) {
            \App\Models\Estados::create([
                'nombre' => $estado
            ]);
        }
    }
}
