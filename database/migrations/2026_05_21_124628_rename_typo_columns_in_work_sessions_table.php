<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('work_sessions', function (Blueprint $table) {
            $table->renameColumn('horara_ingreso_brake', 'hora_ingreso_brake');
            $table->renameColumn('hola_salida', 'hora_salida');
        });
    }

    public function down(): void
    {
        Schema::table('work_sessions', function (Blueprint $table) {
            $table->renameColumn('hora_ingreso_brake', 'horara_ingreso_brake');
            $table->renameColumn('hora_salida', 'hola_salida');
        });
    }
};
