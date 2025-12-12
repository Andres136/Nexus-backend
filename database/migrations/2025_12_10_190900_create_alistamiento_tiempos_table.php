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
        Schema::create('alistamiento_tiempos', function (Blueprint $table) {
            $table->id();
                // Relación con Alistamiento 
                $table->unsignedBigInteger('alistamiento_id');
            $table->foreign('alistamiento_id')
                ->references('id')->on('alistamiento')
                ->onDelete('cascade');

            // Tipo de evento
            $table->enum('tipo', [
                'INICIO',
                'PAUSA',
                'REANUDACION',
                'FINALIZACION',
                'PAUSA_USUARIO'
            ]);

             // Fecha y hora exacta del evento
            $table->dateTime('fecha_hora');

            // Razón (solo aplica para pausa)
            $table->text('razon')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alistamiento_tiempos');
    }
};
