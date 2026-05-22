<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jornada_laborals', function (Blueprint $table) {
            $table->time('hora_entrada')->default('07:00:00')->after('status');
            $table->time('hora_salida_almuerzo')->default('13:00:00')->after('hora_entrada');
            $table->time('hora_ingreso_almuerzo')->default('14:00:00')->after('hora_salida_almuerzo');
            $table->time('hora_salida_pausa')->nullable()->after('hora_ingreso_almuerzo');
            $table->time('hora_ingreso_pausa')->nullable()->after('hora_salida_pausa');
            $table->time('hora_salida')->default('17:00:00')->after('hora_ingreso_pausa');
            $table->unsignedSmallInteger('duracion_pausa_minutos')->default(15)->after('hora_salida');
            $table->unsignedSmallInteger('duracion_almuerzo_minutos')->default(60)->after('duracion_pausa_minutos');
            $table->boolean('comando_voz_activo')->default(true)->after('duracion_almuerzo_minutos');
        });
    }

    public function down(): void
    {
        Schema::table('jornada_laborals', function (Blueprint $table) {
            $table->dropColumn([
                'hora_entrada',
                'hora_salida_almuerzo',
                'hora_ingreso_almuerzo',
                'hora_salida_pausa',
                'hora_ingreso_pausa',
                'hora_salida',
                'duracion_pausa_minutos',
                'duracion_almuerzo_minutos',
                'comando_voz_activo',
            ]);
        });
    }
};
