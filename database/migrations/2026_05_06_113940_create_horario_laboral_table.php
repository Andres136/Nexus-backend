<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horario_laboral', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->timestamp('hora_ingreso')->nullable();
            $table->timestamp('hora_salida')->nullable();
            $table->timestamp('hora_salida_brake')->nullable();
            $table->dateTime('horara_ingreso_brake')->nullable();
            $table->timestamp('hora_salida_almuerzo')->nullable();
            $table->timestamp('hora_ingreso_almuerzo')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horario_laboral');
    }
};
