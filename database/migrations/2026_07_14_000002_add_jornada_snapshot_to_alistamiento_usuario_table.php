<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alistamiento_usuario', function (Blueprint $table) {
            $table->foreignId('jornada_laboral_id')
                ->nullable()
                ->after('usuario_id')
                ->constrained('jornada_laborals')
                ->nullOnDelete();
            $table->decimal('horas_semanales_snapshot', 5, 2)
                ->nullable()
                ->after('jornada_laboral_id');
        });
    }

    public function down(): void
    {
        Schema::table('alistamiento_usuario', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jornada_laboral_id');
            $table->dropColumn('horas_semanales_snapshot');
        });
    }
};
