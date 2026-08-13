<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('convocatoria_cuestionario_respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuestionario_id')->constrained('convocatoria_cuestionarios')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained('convocatoria_cuestionario_preguntas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('valor');
            $table->unsignedTinyInteger('calificacion')->nullable();
            $table->foreignId('calificado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('calificado_en')->nullable();
            $table->timestamps();

            $table->unique(['pregunta_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convocatoria_cuestionario_respuestas');
    }
};
