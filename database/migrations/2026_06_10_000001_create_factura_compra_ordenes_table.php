<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factura_compra_ordenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_compra_id')
                ->constrained('factura_compras')
                ->cascadeOnDelete();
            $table->foreignId('orden_compra_proveedor_id')
                ->constrained('orden_compra_proveedores')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['factura_compra_id', 'orden_compra_proveedor_id'],
                'factura_compra_orden_unique'
            );
        });

        Schema::table('detalles_factura_compra', function (Blueprint $table) {
            $table->unsignedBigInteger('orden_compra_proveedor_detalle_id')
                ->nullable()
                ->after('producto_id');

            $table->foreign('orden_compra_proveedor_detalle_id', 'dfc_ocp_detalle_fk')
                ->references('id')
                ->on('orden_compra_proveedor_detalles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('detalles_factura_compra', function (Blueprint $table) {
            $table->dropForeign('dfc_ocp_detalle_fk');
            $table->dropColumn('orden_compra_proveedor_detalle_id');
        });

        Schema::dropIfExists('factura_compra_ordenes');
    }
};
