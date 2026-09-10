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
        // El único responsable fijo se reemplaza por una lista de participantes (equipo
        // auditor). No había datos reales que preservar (solo auditorías de prueba).
        Schema::table('auditorias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auditorias', function (Blueprint $table) {
            $table->foreignId('responsable_id')->nullable()->after('id')->constrained('users');
        });

        DB::table('auditoria_participantes')->orderBy('auditoria_id')->each(function ($participante) {
            DB::table('auditorias')
                ->where('id', $participante->auditoria_id)
                ->whereNull('responsable_id')
                ->update(['responsable_id' => $participante->user_id]);
        });
    }
};
