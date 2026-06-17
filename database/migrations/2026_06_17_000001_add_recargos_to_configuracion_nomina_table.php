<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion_nomina', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracion_nomina', 'recargo_extra_diurna')) {
                $table->decimal('recargo_extra_diurna', 6, 4)->default(0.2500)->after('porcentaje_pension_empleado');
            }
            if (! Schema::hasColumn('configuracion_nomina', 'recargo_extra_nocturna')) {
                $table->decimal('recargo_extra_nocturna', 6, 4)->default(0.7500)->after('recargo_extra_diurna');
            }
            if (! Schema::hasColumn('configuracion_nomina', 'recargo_festiva')) {
                $table->decimal('recargo_festiva', 6, 4)->default(0.7500)->after('recargo_extra_nocturna');
            }
            if (! Schema::hasColumn('configuracion_nomina', 'recargo_nocturna_festiva')) {
                $table->decimal('recargo_nocturna_festiva', 6, 4)->default(1.1000)->after('recargo_festiva');
            }
            if (! Schema::hasColumn('configuracion_nomina', 'porcentaje_incapacidad')) {
                $table->decimal('porcentaje_incapacidad', 6, 4)->default(0.6667)->after('recargo_nocturna_festiva');
            }
            if (! Schema::hasColumn('configuracion_nomina', 'hora_inicio_nocturna')) {
                $table->time('hora_inicio_nocturna')->default('19:00:00')->after('porcentaje_incapacidad');
            }
            if (! Schema::hasColumn('configuracion_nomina', 'hora_fin_nocturna')) {
                $table->time('hora_fin_nocturna')->default('06:00:00')->after('hora_inicio_nocturna');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_nomina', function (Blueprint $table) {
            $columns = [
                'recargo_extra_diurna',
                'recargo_extra_nocturna',
                'recargo_festiva',
                'recargo_nocturna_festiva',
                'porcentaje_incapacidad',
                'hora_inicio_nocturna',
                'hora_fin_nocturna',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('configuracion_nomina', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
