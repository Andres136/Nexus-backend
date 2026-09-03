<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // El proceso ya no se elige al planificar la pregunta (solo cláusula ISO + texto);
        // se asigna después, al ejecutar la auditoría y calificarla.
        Schema::table('auditoria_preguntas', function (Blueprint $table) {
            $table->foreignId('proceso_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auditoria_preguntas', function (Blueprint $table) {
            $table->foreignId('proceso_id')->nullable(false)->change();
        });
    }
};
