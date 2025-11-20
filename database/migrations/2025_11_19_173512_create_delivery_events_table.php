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
        Schema::create('delivery_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_id')->constrained('orden__compras')->onDelete('cascade');
            $table->date('fecha_entrega');
            $table->time('hora');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->onDelete('cascade');
            $table->decimal('cantidad', 10, 2);
            $table->string('observaciones')->nullable();
            $table->enum('estado', ['pendiente', 'completado', 'cancelado','en_ruta'])->default('pendiente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_events');
    }
};
