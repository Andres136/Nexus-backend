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
        // Personas auditadas (entrevistadas) para esta pregunta — del departamento/proceso
        // asignado, para el informe de auditoría ("PERSONAS AUDITADAS").
        Schema::create('auditoria_pregunta_personas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auditoria_pregunta_id')->constrained('auditoria_preguntas')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['auditoria_pregunta_id', 'user_id'], 'audit_pregunta_persona_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria_pregunta_personas');
    }
};
