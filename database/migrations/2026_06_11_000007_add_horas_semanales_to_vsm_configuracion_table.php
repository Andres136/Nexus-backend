<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vsm_configuracion', function (Blueprint $table) {
            $table->decimal('horas_semanales', 5, 2)
                  ->default(44)
                  ->after('meta_unidades_hora')
                  ->comment('Horas laborales por semana (ej: 44). horas_diarias = horas_semanales / 5');
        });
    }

    public function down(): void
    {
        Schema::table('vsm_configuracion', function (Blueprint $table) {
            $table->dropColumn('horas_semanales');
        });
    }
};
