<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orden_compra_proveedor_detalle_origenes', function (Blueprint $table) {
            if (!Schema::hasColumn('orden_compra_proveedor_detalle_origenes', 'cantidad_prioridad')) {
                $table->decimal('cantidad_prioridad', 10, 2)
                    ->default(0)
                    ->after('cantidad_solicitada');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orden_compra_proveedor_detalle_origenes', function (Blueprint $table) {
            if (Schema::hasColumn('orden_compra_proveedor_detalle_origenes', 'cantidad_prioridad')) {
                $table->dropColumn('cantidad_prioridad');
            }
        });
    }
};
