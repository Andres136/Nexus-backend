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
        // "Proceso auditado" pasa a ser directamente un departamento (el catálogo `procesos` está
        // casi vacío/poco usado; `departamentos` es el catálogo real ya poblado en todo el sistema).
        Schema::table('auditoria_preguntas', function (Blueprint $table) {
            $table->dropForeign(['proceso_id']);
            $table->foreign('proceso_id')->references('id')->on('departamentos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auditoria_preguntas', function (Blueprint $table) {
            $table->dropForeign(['proceso_id']);
            $table->foreign('proceso_id')->references('id')->on('procesos');
        });
    }
};
