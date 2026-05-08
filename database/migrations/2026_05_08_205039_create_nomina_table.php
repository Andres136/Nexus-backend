<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // ¿A qué empleado pertenece esta nómina?
            $table->foreignId('user_id')->constrained('users');

            // Horas trabajadas de diferentes tipos
            $table->integer('horas_normales_trabajada_id')->default(0);
            $table->integer('horas_extras_nocturnas_id')->default(0);
            $table->integer('horas_extras_diurna_id')->default(0);
            $table->integer('horas_festivas_id')->default(0);
            $table->integer('horas_nocturnas_festivas_id')->default(0);

            // ¿Qué descuento tiene este período?
            $table->foreignId('descuento_id')
                  ->nullable()
                  ->constrained('descuentos');

            // ¿De qué registro transaccional viene?
            $table->foreignId('transacional_registros_id')
                  ->nullable()
                  ->constrained('transacional_registros');

            // ¿Qué jornada laboral tiene?
            $table->foreignId('jornada_laboral_id')
                  ->constrained('jornada_laborals');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina');
    }
};
