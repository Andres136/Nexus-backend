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
            'Usuario',
            'Invitado',
            'Hsq',
            'Compras',
            'Inventario',
            'PQRS',
            'Produccion',

        ];

        foreach ($roles as $rol) {
            \App\Models\Roles::create([
                'nombre' => $rol
            ]);
        }
    }
}
