<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las etapas pasan a ser por año (ciclo de planeación): cada año se
     * re-corren las 5. Único ahora por (numero, anio).
     */
    public function up(): void
    {
        Schema::table('bsc_etapas', function (Blueprint $table) {
            $table->unsignedSmallInteger('anio')->default(0)->after('numero');
        });

        DB::table('bsc_etapas')->where('anio', 0)->update(['anio' => (int) now()->year]);

        Schema::table('bsc_etapas', function (Blueprint $table) {
            $table->dropUnique('bsc_etapas_numero_unique');
            $table->unique(['numero', 'anio'], 'bsc_etapas_numero_anio_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bsc_etapas', function (Blueprint $table) {
            $table->dropUnique('bsc_etapas_numero_anio_unique');
            $table->unique('numero', 'bsc_etapas_numero_unique');
            $table->dropColumn('anio');
        });
    }
};
