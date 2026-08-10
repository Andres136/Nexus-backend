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
        // Sin doctrine/dbal instalado: ALTER directo para el enum en vez de Blueprint::change().
        DB::statement("ALTER TABLE inspeccions MODIFY estado_general ENUM('Aprobado', 'Requiere mantenimiento', 'No apto') NULL");

        Schema::table('inspeccions', function (Blueprint $table) {
            $table->date('fecha_realizado')->nullable()->after('fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inspeccions', function (Blueprint $table) {
            $table->dropColumn('fecha_realizado');
        });

        DB::statement("UPDATE inspeccions SET estado_general = 'Aprobado' WHERE estado_general IS NULL");
        DB::statement("ALTER TABLE inspeccions MODIFY estado_general ENUM('Aprobado', 'Requiere mantenimiento', 'No apto') NOT NULL");
    }
};
