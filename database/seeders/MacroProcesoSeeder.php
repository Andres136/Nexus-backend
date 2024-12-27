<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MacroProcesoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $macroprocesos = [
            'ESTRATEGICOS',
            'MISIONALES',
            'DE APOYO',
            ];
            foreach ($macroprocesos as $macroproceso) {
                \App\Models\Macroprocesos::create(['nombre' => $macroproceso]);
            }
    }
}
