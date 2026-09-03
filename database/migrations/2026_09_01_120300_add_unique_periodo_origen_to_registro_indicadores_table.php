<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un indicador tiene como máximo un registro por (periodo, origen). Esto
     * permite que el snapshot use un `upsert` en bloque (1 query) en vez de
     * un updateOrCreate por indicador, y evita duplicados si el cron se
     * solapa.
     */
    public function up(): void
    {
        // Limpia posibles duplicados previos antes de crear el índice único.
        $dupes = DB::table('registro_indicadores')
            ->select('indicador_id', 'periodo', 'origen', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as c'))
            ->whereNotNull('periodo')
            ->groupBy('indicador_id', 'periodo', 'origen')
            ->having('c', '>', 1)
            ->get();

        foreach ($dupes as $d) {
            DB::table('registro_indicadores')
                ->where('indicador_id', $d->indicador_id)
                ->where('periodo', $d->periodo)
                ->where('origen', $d->origen)
                ->where('id', '!=', $d->keep_id)
                ->delete();
        }

        Schema::table('registro_indicadores', function (Blueprint $table) {
            $table->unique(['indicador_id', 'periodo', 'origen'], 'reg_ind_periodo_origen_unique');
            // El índice único (indicador_id, periodo, origen) ya cubre los
            // prefijos (indicador_id) y (indicador_id, periodo); el no-único
            // que creó la migración anterior queda redundante.
            $table->dropIndex('registro_indicadores_indicador_id_periodo_index');
        });
    }

    public function down(): void
    {
        Schema::table('registro_indicadores', function (Blueprint $table) {
            $table->dropUnique('reg_ind_periodo_origen_unique');
            $table->index(['indicador_id', 'periodo']);
        });
    }
};
