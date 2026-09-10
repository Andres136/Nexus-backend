<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('convocatoria_cuestionarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convocatoria_id')->constrained('convocatorias')->cascadeOnDelete();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('duracion_segundos')->default(300);
            $table->string('estado')->default('borrador'); // borrador | publicado | cerrado
            $table->timestamp('publicado_en')->nullable();
            $table->timestamp('cerrado_en')->nullable();
            $table->foreignId('creado_por')->constrained('users');
            $table->timestamps();

            $table->unique('convocatoria_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convocatoria_cuestionarios');
    }
};
