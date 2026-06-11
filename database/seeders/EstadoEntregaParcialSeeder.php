<?php

namespace Database\Seeders;

use App\EstadoEnum;
use App\Models\Estados;
use Illuminate\Database\Seeder;

class EstadoEntregaParcialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Estados::updateOrCreate(
            ['id' => EstadoEnum::ENTREGA_PARCIAL->value],
            ['nombre' => EstadoEnum::ENTREGA_PARCIAL->nombre()]
        );
    }
}
