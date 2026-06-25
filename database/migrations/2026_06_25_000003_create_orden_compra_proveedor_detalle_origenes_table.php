<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_compra_proveedor_detalle_origenes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orden_compra_proveedor_detalle_id');
            $table->unsignedBigInteger('orden_compra_id');
            $table->unsignedBigInteger('orden_compra_detalle_id');
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->unsignedBigInteger('sede_id')->nullable();
            $table->unsignedBigInteger('bodega_id')->nullable();
            $table->decimal('cantidad_solicitada', 10, 2);
            $table->decimal('cantidad_prioridad', 10, 2)->default(0);
            $table->decimal('cantidad_recibida_aplicada', 10, 2)->default(0);
            $table->json('prioridad_snapshot')->nullable();
            $table->timestamps();

            $table->foreign('orden_compra_proveedor_detalle_id', 'ocp_orig_det_fk')
                ->references('id')
                ->on('orden_compra_proveedor_detalles')
                ->cascadeOnDelete();
            $table->foreign('orden_compra_id', 'ocp_orig_oc_fk')
                ->references('id')
                ->on('orden__compras')
                ->cascadeOnDelete();
            $table->foreign('orden_compra_detalle_id', 'ocp_orig_ocd_fk')
                ->references('id')
                ->on('orden__compra__detalles')
                ->cascadeOnDelete();
            $table->foreign('producto_id', 'ocp_orig_prod_fk')
                ->references('id')
                ->on('products')
                ->nullOnDelete();
            $table->foreign('sede_id', 'ocp_orig_sede_fk')
                ->references('id')
                ->on('sedes')
                ->nullOnDelete();
            $table->foreign('bodega_id', 'ocp_orig_bodega_fk')
                ->references('id')
                ->on('bodegas')
                ->nullOnDelete();

            $table->index('orden_compra_id', 'ocp_origenes_oc_idx');
            $table->index('orden_compra_detalle_id', 'ocp_origenes_oc_detalle_idx');
            $table->index(['producto_id', 'sede_id'], 'ocp_origenes_producto_sede_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_compra_proveedor_detalle_origenes');
    }
};
