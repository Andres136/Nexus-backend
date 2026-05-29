<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encuesta_preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encuesta_id')->constrained('encuestas')->cascadeOnDelete();
            $table->string('texto');
            $table->enum('tipo', ['texto', 'escala', 'opcion_multiple']);
            $table->json('opciones')->nullable();
            $table->unsignedTinyInteger('orden')->default(0);
            $table->boolean('requerida')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encuesta_preguntas');
    }
};
