<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Permite guardar respuestas en borrador mientras el postulante
        // escribe (autoguardado) sin que cuenten como "ya respondió": solo
        // cuando enviado_en tiene valor la respuesta es definitiva y visible
        // para quien califica.
        Schema::table('convocatoria_cuestionario_respuestas', function (Blueprint $table) {
            $table->timestamp('enviado_en')->nullable()->after('valor');
        });
    }

    public function down(): void
    {
        Schema::table('convocatoria_cuestionario_respuestas', function (Blueprint $table) {
            $table->dropColumn('enviado_en');
        });
    }
};
