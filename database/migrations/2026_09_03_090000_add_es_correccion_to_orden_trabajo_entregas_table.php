<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orden_trabajo_entregas', function (Blueprint $table) {
            // Distingue las filas generadas por una corrección del total enviado
            // (pueden llevar cantidad negativa) de las entregas normales.
            $table->boolean('es_correccion')->default(false)->after('observaciones');
        });
    }

    public function down(): void
    {
        Schema::table('orden_trabajo_entregas', function (Blueprint $table) {
            $table->dropColumn('es_correccion');
        });
    }
};
