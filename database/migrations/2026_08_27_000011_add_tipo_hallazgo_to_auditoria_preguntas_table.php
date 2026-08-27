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
        // Clasificación del hallazgo para el informe de auditoría (formato ya usado por la
        // empresa): No Conformidad, Oportunidad de Mejora, Observación o Fortaleza.
        Schema::table('auditoria_preguntas', function (Blueprint $table) {
            $table->string('tipo_hallazgo')->nullable()->after('proceso_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auditoria_preguntas', function (Blueprint $table) {
            $table->dropColumn('tipo_hallazgo');
        });
    }
};
