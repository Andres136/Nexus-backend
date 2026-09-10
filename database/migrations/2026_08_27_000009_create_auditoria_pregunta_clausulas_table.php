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
        // Una pregunta puede homologar varias normas/cláusulas a la vez (ej. un mismo hallazgo
        // aplica tanto a ISO 9001 como a ISO 14001), así que deja de ser una FK única.
        Schema::create('auditoria_pregunta_clausulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auditoria_pregunta_id')->constrained('auditoria_preguntas')->onDelete('cascade');
            $table->foreignId('clausula_iso_id')->constrained('clausulas_iso');
            $table->timestamps();

            $table->unique(['auditoria_pregunta_id', 'clausula_iso_id'], 'audit_pregunta_clausula_unique');
        });

        Schema::table('auditoria_preguntas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('clausula_iso_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auditoria_preguntas', function (Blueprint $table) {
            $table->foreignId('clausula_iso_id')->nullable()->after('proceso_id')->constrained('clausulas_iso');
        });

        Schema::dropIfExists('auditoria_pregunta_clausulas');
    }
};
