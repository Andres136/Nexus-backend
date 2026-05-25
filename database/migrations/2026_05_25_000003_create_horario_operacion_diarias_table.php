<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horario_operacion_diarias', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->date('fecha')->unique();
            $table->foreignId('jornada_laboral_id')->nullable()->constrained('jornada_laborals');
            $table->time('hora_salida_pausa')->nullable();
            $table->time('hora_ingreso_pausa')->nullable();
            $table->time('hora_salida_almuerzo')->nullable();
            $table->time('hora_ingreso_almuerzo')->nullable();
            $table->unsignedSmallInteger('duracion_pausa_minutos')->nullable();
            $table->unsignedSmallInteger('duracion_almuerzo_minutos')->nullable();
            $table->string('motivo', 180)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horario_operacion_diarias');
    }
};
