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
        Schema::create('traslados_bodega', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->foreignId('bodega_origen_id')->references('id')->on('bodegas')->onDelete('cascade');
            $table->foreignId('bodega_destino_id')->references('id')->on('bodegas')->onDelete('cascade');
            $table->foreignId('usuario_aprobador_bodega_id')
    ->nullable()
    ->constrained('users')
    ->nullOnDelete();

$table->foreignId('usuario_aprobador_inventario_id')
    ->nullable()
    ->constrained('users')
    ->nullOnDelete();

            $table->foreignId('usuario_creador_id')->references('id')->on('users')->onDelete('cascade');
        $table->enum('estado', [
    'PENDIENTE_BODEGA',
    'RECHAZADO_BODEGA',
    'PENDIENTE_INVENTARIO',
    'RECHAZADO_INVENTARIO',
    'APROBADO',
    'DESPACHADO',
])->default('PENDIENTE_BODEGA');

            $table->decimal('cantidad_total', 10, 2)->default(0);
            $table->date('fecha_despacho')->nullable();
            $table->date('fecha_recepcion')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traslados_bodega');
    }
};
