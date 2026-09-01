<?php

namespace Database\Seeders;

use App\Models\Bsc\BscPerspectiva;
use Illuminate\Database\Seeder;

class BscPerspectivaSeeder extends Seeder
{
    /**
     * Las 4 perspectivas del BSC con los colores del mapa estratégico
     * (mismos del Excel: financiera=naranja, cliente=morado, procesos=azul,
     * aprendizaje=verde). El icono lo sube el admin después.
     */
    public function run(): void
    {
        $perspectivas = [
            ['clave' => 'financiera',  'nombre' => 'Financiera',              'color' => '#F59E0B', 'orden' => 1],
            ['clave' => 'cliente',     'nombre' => 'Cliente',                 'color' => '#7C3AED', 'orden' => 2],
            ['clave' => 'procesos',    'nombre' => 'Procesos',                'color' => '#2563EB', 'orden' => 3],
            ['clave' => 'aprendizaje', 'nombre' => 'Aprendizaje y Desarrollo', 'color' => '#16A34A', 'orden' => 4],
        ];

        foreach ($perspectivas as $p) {
            BscPerspectiva::updateOrCreate(['clave' => $p['clave']], $p);
        }
    }
}
