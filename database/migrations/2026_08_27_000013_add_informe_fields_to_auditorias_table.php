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
        // Campos del informe de auditoría (formato ya usado por la empresa): el resto de la
        // información del informe (proceso auditado, equipo auditor, personas auditadas,
        // criterios/normas) se deriva de las preguntas ya capturadas, no hace falta duplicarla.
        Schema::table('auditorias', function (Blueprint $table) {
            $table->string('lugar')->nullable()->after('hora');
            $table->text('objetivo')->nullable()->after('lugar');
            $table->text('alcance')->nullable()->after('objetivo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auditorias', function (Blueprint $table) {
            $table->dropColumn(['lugar', 'objetivo', 'alcance']);
        });
    }
};
