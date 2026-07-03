<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horario_operacion_diarias', function (Blueprint $table) {
            if (Schema::hasIndex('horario_operacion_diarias', 'horario_operacion_diarias_fecha_kiosko_unique')) {
                $table->dropUnique('horario_operacion_diarias_fecha_kiosko_unique');
            }

            if (! Schema::hasColumn('horario_operacion_diarias', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('kiosko_device_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            $table->index(['fecha', 'kiosko_device_id', 'user_id'], 'horario_operacion_fecha_kiosko_user_idx');
        });
    }

    public function down(): void
    {
        Schema::table('horario_operacion_diarias', function (Blueprint $table) {
            if (Schema::hasIndex('horario_operacion_diarias', 'horario_operacion_fecha_kiosko_user_idx')) {
                $table->dropIndex('horario_operacion_fecha_kiosko_user_idx');
            }

            if (Schema::hasColumn('horario_operacion_diarias', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }

            $table->unique(['fecha', 'kiosko_device_id'], 'horario_operacion_diarias_fecha_kiosko_unique');
        });
    }
};
