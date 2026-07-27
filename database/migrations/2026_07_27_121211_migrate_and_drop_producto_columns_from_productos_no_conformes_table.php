<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Un reporte podía tener un solo producto (producto_id/cantidad_afectada
        // sueltos en la tabla). Se mueve esa relación 1:1 a producto_no_conforme_items
        // para poder soportar varios productos por reporte.
        DB::table('productos_no_conformes')
            ->whereNotNull('producto_id')
            ->select('id', 'producto_id', 'cantidad_afectada')
            ->orderBy('id')
            ->get()
            ->each(function ($row) {
                DB::table('producto_no_conforme_items')->insert([
                    'producto_no_conforme_id' => $row->id,
                    'producto_id' => $row->producto_id,
                    'cantidad_afectada' => $row->cantidad_afectada ?? 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('productos_no_conformes', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->dropColumn(['producto_id', 'cantidad_afectada']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos_no_conformes', function (Blueprint $table) {
            $table->foreignId('producto_id')->nullable()->after('proceso_id')->constrained('products');
            $table->integer('cantidad_afectada')->default(1);
        });

        // Best-effort: trae de vuelta el primer item de cada reporte.
        DB::table('producto_no_conforme_items')
            ->orderBy('producto_no_conforme_id')
            ->orderBy('id')
            ->get()
            ->groupBy('producto_no_conforme_id')
            ->each(function ($items, $ncId) {
                $primero = $items->first();
                DB::table('productos_no_conformes')->where('id', $ncId)->update([
                    'producto_id' => $primero->producto_id,
                    'cantidad_afectada' => $primero->cantidad_afectada,
                ]);
            });
    }
};
