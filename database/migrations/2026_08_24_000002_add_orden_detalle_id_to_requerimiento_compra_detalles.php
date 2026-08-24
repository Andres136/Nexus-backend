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
        Schema::table('requerimiento_compra_detalles', function (Blueprint $table) {
            $table->foreignId('orden_compra_proveedor_detalle_id')
                ->nullable()
                ->after('proveedor_sugerido_id')
                ->constrained('orden_compra_proveedor_detalles', 'id', 'req_compra_det_oc_detalle_fk')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requerimiento_compra_detalles', function (Blueprint $table) {
            $table->dropForeign('req_compra_det_oc_detalle_fk');
            $table->dropColumn('orden_compra_proveedor_detalle_id');
        });
    }
};
