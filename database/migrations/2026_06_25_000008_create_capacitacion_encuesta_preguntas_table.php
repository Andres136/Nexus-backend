<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capacitacion_encuesta_preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encuesta_id')->constrained('capacitacion_encuestas')->cascadeOnDelete();
            $table->string('texto', 500);
            $table->string('tipo', 30);
            $table->json('opciones')->nullable();
            $table->unsignedTinyInteger('orden')->default(0);
            $table->boolean('requerida')->default(true);
            $table->unsignedTinyInteger('max_escala')->nullable();
            $table->timestamps();

            $table->index(['encuesta_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capacitacion_encuesta_preguntas');
    }
};
