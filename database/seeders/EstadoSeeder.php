<?php

namespace Database\Seeders;

use App\EstadoEnum;
use App\Models\Estados;
use Illuminate\Database\Seeder;

class EstadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estados = [
            EstadoEnum::PENDIENTE,
            EstadoEnum::COMPLETADO,
            EstadoEnum::ACTIVO,
            EstadoEnum::INACTIVO,
            EstadoEnum::ENTREGA_PARCIAL,
            EstadoEnum::PAGADA,
            EstadoEnum::PAGO_PARCIAL,
            EstadoEnum::ANULADA,
        ];

        foreach ($estados as $estado) {
            Estados::updateOrCreate(
                ['id' => $estado->value],
                ['nombre' => $estado->nombre()]
            );
        }
    }
}
