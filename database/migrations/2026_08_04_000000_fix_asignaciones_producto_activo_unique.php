<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Los valores 2 eran un parche para esquivar el unique(producto_id, activo);
        // se normalizan a 0 (inactiva) antes de reemplazar la restricción.
        DB::table('asignaciones')->where('activo', 2)->update(['activo' => 0]);

        Schema::table('asignaciones', function ($table) {
            $table->dropUnique(['producto_id', 'activo']);
        });

        // Unique index sobre una columna generada que solo tiene valor cuando
        // la asignación está activa: así solo se exige un producto activo a la
        // vez, sin limitar cuántas asignaciones históricas (activo = 0) existan.
        DB::statement(
            'ALTER TABLE asignaciones ADD COLUMN producto_activo_unico BIGINT UNSIGNED
             GENERATED ALWAYS AS (IF(activo = 1, producto_id, NULL)) VIRTUAL'
        );

        DB::statement(
            'ALTER TABLE asignaciones ADD UNIQUE KEY asignaciones_producto_activo_unico (producto_activo_unico)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE asignaciones DROP INDEX asignaciones_producto_activo_unico');
        DB::statement('ALTER TABLE asignaciones DROP COLUMN producto_activo_unico');

        Schema::table('asignaciones', function ($table) {
            $table->unique(['producto_id', 'activo']);
        });
    }
};
