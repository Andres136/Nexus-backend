<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('kiosko_id')->constrained('kiosko_devices');
            $table->date('registro_diario');
            $table->timestamp('hora_entrada')->nullable();
            $table->datetime('hola_salida')->nullable();
            $table->timestamp('hora_salida_brake')->nullable();
            $table->datetime('horara_ingreso_brake')->nullable();
            $table->timestamp('hora_salida_almuerzo')->nullable();
            $table->timestamp('hora_ingreso_almuerzo')->nullable();
            $table->integer('minutos_trabajados')->default(0);
            $table->integer('minutos_pausa')->default(0);
            $table->integer('minutos_tardanza')->default(0);
            $table->decimal('sabado_minutos', 8, 2)->default(0);
            $table->decimal('festivo_minutos', 8, 2)->default(0);
            $table->foreignId('horario_laboral_id')->constrained('jornada_laborals');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_sessions');
    }
}; 
