<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capacitacion_encuesta_preguntas', function (Blueprint $table) {
            $table->string('respuesta_correcta', 500)->nullable()->after('max_escala');
        });
    }

    public function down(): void
    {
        Schema::table('capacitacion_encuesta_preguntas', function (Blueprint $table) {
            $table->dropColumn('respuesta_correcta');
        });
    }
};
