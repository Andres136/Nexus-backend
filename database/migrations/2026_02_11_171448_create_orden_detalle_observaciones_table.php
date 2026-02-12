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
        Schema::create('orden_detalle_observaciones', function (Blueprint $table) {
           $table->id();

    $table->foreignId('orden_detalle_id')
          ->constrained('orden_compra_proveedor_detalles')
          ->cascadeOnDelete();

    $table->text('observacion');

    $table->foreignId('usuario_id')
          ->constrained('users')
          ->cascadeOnDelete();

    $table->foreignId('proceso_bolsas_id')
          ->nullable()
          ->constrained('proceso_bolsas')
          ->nullOnDelete();

    $table->foreignId('proveedor_id')
          ->nullable()
          ->constrained('proveedores')
          ->nullOnDelete();

    $table->string('estado', 20)->default('pendiente');

    $table->timestamps();

    $table->index(['orden_detalle_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_detalle_observaciones');
    }
};
