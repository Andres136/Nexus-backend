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
        Schema::create('alistamientos_ot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_trabajo_id')->constrained('orden_de_trabajos')->onDelete('cascade');
            $table->foreignId('orden_compra_detalle_id')->constrained('orden__compra__detalles')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('bodega_id')->constrained('bodegas')->onDelete('cascade');
            $table->decimal('cantidad', 10, 2);
            $table->enum('tipo',['original','equivalente']);
            $table->text('observacion')->nullable();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('fecha_alistamiento')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alistamientos_ot');
    }
};
