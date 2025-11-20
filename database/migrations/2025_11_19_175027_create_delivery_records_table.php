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
        Schema::create('delivery_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_event_id')->constrained('delivery_events')->onDelete('cascade');
             $table->date('fecha_real')->nullable();
            $table->time('hora_real')->nullable();
    // Datos de la entrega REAL
            $table->decimal('cantidad_entregada', 10, 2)->default(0);
            // Resultado de la entrega
            $table->enum('resultado', ['entregado', 'parcial', 'no_entregado'])
                  ->default('entregado');

            // Motivo en caso de no entrega o parcial
            $table->string('motivo_no_entrega')->nullable();

            // Observaciones del conductor / usuario
            $table->text('observaciones')->nullable();

            // Quién registró este movimiento
            $table->foreignId('usuario_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_records');
    }
};
