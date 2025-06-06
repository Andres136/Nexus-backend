<?php

namespace Database\Seeders;

use App\Models\Estados;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EstadoEntregaParcialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Estados::firstOrCreate(['nombre' => 'Entrega Parcial']);
    }
}
