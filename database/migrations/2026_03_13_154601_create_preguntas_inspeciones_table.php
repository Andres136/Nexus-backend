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
        Schema::create('preguntas_inspecciones', function (Blueprint $table) {
            $table->id();
          $table->foreignId('tipo_inspeccion_id')->references('id')->on('tipo_inspecciones')->onDelete('cascade');
            $table->string('pregunta');
           $table->boolean('tipo_respuesta')->default(0); // 0 = texto, 1 = opción múltiple
           $table->boolean('activa')->default(true);
           $table->integer('orden')->default(0);
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preguntas_inspecciones');
    }
};
