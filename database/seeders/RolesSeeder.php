<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'Administrador',
            'HSEQ',
            'Invitado',
            'Administativo',
            'Compras',
            'Inventario',
            'Comercial',
            'Transporte',
            'Ejecutivo Comercial',

        ];

        foreach ($roles as $rol) {
            \App\Models\Roles::create([
                'nombre' => $rol
            ]);
        }
    }
}
