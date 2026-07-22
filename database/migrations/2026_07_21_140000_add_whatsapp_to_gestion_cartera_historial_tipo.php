<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // El formulario y la validación ya permiten 'WHATSAPP' como tipo de
        // gestión, pero el ENUM de la columna nunca se actualizó, causando un
        // error de MySQL (500) al insertar en vez del 422 esperado.
        DB::statement("ALTER TABLE gestion_cartera_historial MODIFY COLUMN tipo ENUM('LLAMADA','EMAIL','VISITA','PROMESA_PAGO','WHATSAPP','OTRO') NOT NULL DEFAULT 'OTRO'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE gestion_cartera_historial MODIFY COLUMN tipo ENUM('LLAMADA','EMAIL','VISITA','PROMESA_PAGO','OTRO') NOT NULL DEFAULT 'OTRO'");
    }
};
