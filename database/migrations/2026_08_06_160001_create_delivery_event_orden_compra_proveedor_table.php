<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_event_orden_compra_proveedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_event_id')->constrained('delivery_events')->cascadeOnDelete();
            $table->unsignedBigInteger('orden_compra_proveedor_id');
            $table->foreign('orden_compra_proveedor_id', 'delivery_event_ocp_orden_foreign')
                ->references('id')->on('orden_compra_proveedores')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['delivery_event_id', 'orden_compra_proveedor_id'], 'delivery_event_ocp_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_event_orden_compra_proveedor');
    }
};
