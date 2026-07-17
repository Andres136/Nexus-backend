<?php

namespace Database\Seeders;

use App\Models\Productividad\CategoriaActividad;
use Illuminate\Database\Seeder;

class CategoriaActividadSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            'Reuniones',
            'Soporte',
            'Visita a bodega',
            'Visita comercial',
            'Capacitación',
            'Diligencia',
            'Mantenimiento',
            'Gestion Comercial',
        ];

        foreach ($categorias as $nombre) {
            CategoriaActividad::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
