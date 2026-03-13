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
        Schema::create('respuesta_inspecciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspeccion_id')->references('id')->on('inspecciones_hseq')->onDelete('cascade');
            $table->foreignId('pregunta_inspeccion_id')->references('id')->on('preguntas_inspecciones')->onDelete('cascade');
            $table->boolean('respuesta')->default(false);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('respuesta_inspecciones');
    }
};
