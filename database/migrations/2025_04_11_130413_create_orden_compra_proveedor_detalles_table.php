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
        Schema::create('orden_compra_proveedor_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_id')->constrained('orden_compra_proveedores')->onDelete('cascade');
            $table->string('descripcion');
            $table->decimal('cantidad_solicitada', 10, 2);
            $table->decimal('cantidad_entregada', 10, 2)->default(0);
            $table->integer('item');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_compra_proveedor_detalles');
    }
};
