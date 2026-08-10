<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_record_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_record_id')->constrained('delivery_records')->cascadeOnDelete();
            $table->unsignedBigInteger('orden_compra_proveedor_detalle_id');
            $table->foreign('orden_compra_proveedor_detalle_id', 'delivery_record_detalles_ocp_detalle_foreign')
                ->references('id')->on('orden_compra_proveedor_detalles')->cascadeOnDelete();
            $table->decimal('cantidad_recogida', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_record_detalles');
    }
};
