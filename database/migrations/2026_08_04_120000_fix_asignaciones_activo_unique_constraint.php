<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El unique(producto_id, activo) original exige que CADA valor de
     * `activo` (0, 1, 2...) sea único por producto — incluidos los
     * históricos inactivos. Eso bloquea desactivar un equipo en cuanto ya
     * tuvo más de un ciclo de asignación/devolución (el parche de
     * AsignacionesService que "empujaba" el inactivo viejo a activo=2 se
     * queda sin cupo y truena con UniqueConstraintViolationException).
     *
     * Lo reemplazamos por una unicidad condicional vía columna generada:
     * solo se exige una fila activa (activo=1) por producto; el historial
     * de inactivos queda libre, como corresponde a una tabla de historial.
     */
    public function up(): void
    {
        Schema::table('asignaciones', function (Blueprint $table) {
            $table->dropUnique('asignaciones_producto_id_activo_unique');
        });

        // Normaliza los valores "basura" (2, 3, ...) que dejó el parche
        // anterior de vuelta a 0 (inactivo real) — ya no hay restricción
        // de unicidad que lo impida.
        DB::table('asignaciones')->where('activo', '>', 1)->update(['activo' => 0]);

        Schema::table('asignaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('producto_id_si_activo')
                ->nullable()
                ->virtualAs('IF(activo = 1, producto_id, NULL)');
            $table->unique('producto_id_si_activo', 'asignaciones_producto_activo_unico');
        });
    }

    public function down(): void
    {
        Schema::table('asignaciones', function (Blueprint $table) {
            $table->dropUnique('asignaciones_producto_activo_unico');
            $table->dropColumn('producto_id_si_activo');
        });

        Schema::table('asignaciones', function (Blueprint $table) {
            $table->unique(['producto_id', 'activo'], 'asignaciones_producto_id_activo_unique');
        });
    }
};
