<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_usuario_semanales', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana');
            $table->foreignId('jornada_laboral_id')->nullable()->constrained('jornada_laborals')->nullOnDelete();
            $table->time('hora_entrada')->nullable();
            $table->time('hora_entrada_limite')->nullable();
            $table->time('hora_salida_pausa')->nullable();
            $table->time('hora_ingreso_pausa')->nullable();
            $table->time('hora_salida_almuerzo')->nullable();
            $table->time('hora_ingreso_almuerzo')->nullable();
            $table->time('hora_salida')->nullable();
            $table->unsignedSmallInteger('duracion_pausa_minutos')->nullable();
            $table->unsignedSmallInteger('duracion_almuerzo_minutos')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'dia_semana'], 'horarios_usuario_semana_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_usuario_semanales');
    }
};
