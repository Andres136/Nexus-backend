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
        Schema::create('alistamiento', function (Blueprint $table) {
            $table->id();
              // Relación con Orden de Trabajo
            $table->unsignedBigInteger('orden_trabajo_id');
            $table->foreign('orden_trabajo_id')
                ->references('id')->on('orden_de_trabajos')
                ->onDelete('cascade');
       
            // Usuario asignado al alistamiento
            $table->unsignedBigInteger('usuario_id');
            $table->foreign('usuario_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
 // Unidades alistadas
            $table->integer('cantidad')->default(0);

            // Tiempo productivo total en segundos
            $table->integer('duracion_segundos')->default(0);

            // Estados del alistamiento
            $table->enum('estado', [
                'INICIADO',
                'PAUSADO',
                'REANUDADO',
                'FINALIZADO'
            ])->default('INICIADO');
               // Auditoría
            $table->date('fecha')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alistamiento');
    }
};
