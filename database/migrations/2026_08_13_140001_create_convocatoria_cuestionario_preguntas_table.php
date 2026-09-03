<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('convocatoria_cuestionario_preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuestionario_id')->constrained('convocatoria_cuestionarios')->cascadeOnDelete();
            $table->text('texto');
            $table->string('tipo')->default('texto'); // texto | opcion_multiple
            $table->json('opciones')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convocatoria_cuestionario_preguntas');
    }
};
