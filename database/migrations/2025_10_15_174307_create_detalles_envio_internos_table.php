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
        Schema::create('detalles_envio_internos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('envio_interno_id')->constrained('envios_internos');
            $table->foreignId('product_id')->constrained('products');
            $table->string('code_id')->nullable();
            $table->string('descripcion')->nullable();
            $table->decimal('cantidad', 15, 2);
            $table->foreignId('bodega_origen_id')->constrained('bodegas');
            $table->integer('item')->nullable();
         
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_envio_internos');
    }
};
