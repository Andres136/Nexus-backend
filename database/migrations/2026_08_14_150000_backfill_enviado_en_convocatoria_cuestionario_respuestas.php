<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // La migración que agregó enviado_en no traía dato de respaldo: a las
        // respuestas que ya existían (todas eran finales, el concepto de
        // borrador/autoguardado no existía antes) les quedó enviado_en NULL,
        // lo que las hacía ver como "no enviadas" — desaparecían de "ya
        // respondiste" y del panel de calificación. Se marcan como enviadas
        // usando su propia fecha de creación.
        DB::table('convocatoria_cuestionario_respuestas')
            ->whereNull('enviado_en')
            ->update(['enviado_en' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        // No se revierte: no hay forma confiable de distinguir cuáles
        // enviado_en fueron puestos por este backfill vs. enviados de verdad
        // después, y volver a poner NULL rompería otra vez lo mismo que esto
        // arregla.
    }
};
