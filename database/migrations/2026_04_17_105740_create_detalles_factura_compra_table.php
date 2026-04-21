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
        Schema::create('detalles_factura_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_compra_id')->constrained('factura_compras')->onDelete('cascade');
            $table->foreignId('bodega_id')->constrained('bodegas')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('products')->onDelete('cascade');
            $table->decimal('cantidad', 15, 2);
            $table->decimal('precio_unitario', 15, 2);
            $table->decimal('total', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_factura_compra');
    }
};
