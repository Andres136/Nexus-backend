<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('horario_operacion_diarias')) {
            return;
        }

        if (Schema::hasIndex('horario_operacion_diarias', 'horario_operacion_diarias_fecha_unique')) {
            Schema::table('horario_operacion_diarias', function (Blueprint $table) {
                $table->dropUnique('horario_operacion_diarias_fecha_unique');
            });
        }

        Schema::table('horario_operacion_diarias', function (Blueprint $table) {
            if (! Schema::hasColumn('horario_operacion_diarias', 'kiosko_device_id')) {
                $table->foreignId('kiosko_device_id')
                    ->nullable()
                    ->after('fecha')
                    ->constrained('kiosko_devices')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('horario_operacion_diarias', 'hora_entrada')) {
                $table->time('hora_entrada')->nullable()->after('jornada_laboral_id');
            }

            if (! Schema::hasColumn('horario_operacion_diarias', 'hora_entrada_limite')) {
                $table->time('hora_entrada_limite')->nullable()->after('hora_entrada');
            }

            if (! Schema::hasColumn('horario_operacion_diarias', 'hora_salida')) {
                $table->time('hora_salida')->nullable()->after('hora_ingreso_almuerzo');
            }
        });

        if (! Schema::hasIndex('horario_operacion_diarias', 'horario_operacion_diarias_fecha_kiosko_unique')) {
            Schema::table('horario_operacion_diarias', function (Blueprint $table) {
                $table->unique(
                    ['fecha', 'kiosko_device_id'],
                    'horario_operacion_diarias_fecha_kiosko_unique'
                );
            });
        }
    }

    public function down(): void
    {
        // Compatibility migration: the canonical create migration already defines this schema.
    }
};
