<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capacitacion_encuesta_respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('envio_id')->constrained('capacitacion_encuesta_envios')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained('capacitacion_encuesta_preguntas')->cascadeOnDelete();
            $table->text('valor');
            $table->timestamps();

            $table->unique(['envio_id', 'pregunta_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capacitacion_encuesta_respuestas');
    }
};
