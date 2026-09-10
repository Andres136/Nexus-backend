<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Departamento (proceso) al que se le hará la auditoría: se decide al programarla.
     * nullable en la columna por las auditorías ya existentes; para las nuevas es obligatorio
     * (validado en StoreAuditoriaRequest).
     */
    public function up(): void
    {
        Schema::table('auditorias', function (Blueprint $table) {
            $table->foreignId('departamento_id')
                ->nullable()
                ->after('creado_por')
                ->constrained('departamentos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('auditorias', function (Blueprint $table) {
            $table->dropForeign(['departamento_id']);
            $table->dropColumn('departamento_id');
        });
    }
};
