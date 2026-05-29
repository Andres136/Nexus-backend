<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encuesta_respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('envio_id')->constrained('encuesta_envios')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained('encuesta_preguntas')->cascadeOnDelete();
            $table->text('valor');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encuesta_respuestas');
    }
};
