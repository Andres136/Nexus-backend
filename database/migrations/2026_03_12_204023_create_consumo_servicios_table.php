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
        Schema::create('consumo_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->references('id')->on('sedes')->onDelete('cascade');
            $table->foreignId('tipo_servicio_id')->references('id')->on('tipo_servicios')->onDelete('cascade');
            $table->decimal('valor_factura', 10, 2)->nullable();
            $table->decimal('consumo', 10, 2);
            $table->date('fecha_consumo');
            $table->date('fecha_pago')->nullable();
             $table->enum('estado', ['pendiente', 'pagado'])->default('pendiente');
             $table->decimal('consumo_percapita', 10, 2)->nullable();
      
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumo_servicios');
    }
};
