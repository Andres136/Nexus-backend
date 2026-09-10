<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('tipo', ['solicitud', 'soporte_equipo', 'desarrollo_nexus'])
                ->default('solicitud')
                ->after('departamento_id');
        });

        // Tickets históricos: si ya tenían un equipo asociado, se consideran
        // soporte de equipo; el resto quedan como solicitud (valor por defecto).
        DB::table('tickets')->whereNotNull('producto_id')->update(['tipo' => 'soporte_equipo']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
