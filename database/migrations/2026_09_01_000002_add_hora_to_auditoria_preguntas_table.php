<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hora (agenda) de cada pregunta al ejecutar la auditoría: sirve para ordenar las preguntas
     * por hora dentro de cada proceso. Opcional.
     */
    public function up(): void
    {
        Schema::table('auditoria_preguntas', function (Blueprint $table) {
            $table->time('hora')->nullable()->after('proceso_id');
        });
    }

    public function down(): void
    {
        Schema::table('auditoria_preguntas', function (Blueprint $table) {
            $table->dropColumn('hora');
        });
    }
};
