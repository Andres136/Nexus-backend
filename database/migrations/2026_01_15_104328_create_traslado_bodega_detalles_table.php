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
        Schema::create('traslado_bodega_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('traslado_bodega_id')->references('id')->on('traslados_bodega')->onDelete('cascade');
            $table->foreignId('producto_id')->references('id')->on('products')->onDelete('cascade');
            $table->decimal('cantidad', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traslado_bodega_detalles');
    }
};
