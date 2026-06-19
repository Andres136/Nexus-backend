<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion_nomina', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracion_nomina', 'porcentaje_salud_empleador')) {
                $table->decimal('porcentaje_salud_empleador', 6, 3)->default(8.500)->after('porcentaje_pension_empleado');
            }
            if (! Schema::hasColumn('configuracion_nomina', 'porcentaje_pension_empleador')) {
                $table->decimal('porcentaje_pension_empleador', 6, 3)->default(12.000)->after('porcentaje_salud_empleador');
            }
            if (! Schema::hasColumn('configuracion_nomina', 'porcentaje_arl')) {
                $table->decimal('porcentaje_arl', 6, 3)->default(2.436)->after('porcentaje_pension_empleador');
            }
            if (! Schema::hasColumn('configuracion_nomina', 'porcentaje_sena')) {
                $table->decimal('porcentaje_sena', 6, 3)->default(2.000)->after('porcentaje_arl');
            }
            if (! Schema::hasColumn('configuracion_nomina', 'porcentaje_icbf')) {
                $table->decimal('porcentaje_icbf', 6, 3)->default(3.000)->after('porcentaje_sena');
            }
            if (! Schema::hasColumn('configuracion_nomina', 'porcentaje_caja_compensacion')) {
                $table->decimal('porcentaje_caja_compensacion', 6, 3)->default(4.000)->after('porcentaje_icbf');
            }
        });

        Schema::table('nomina', function (Blueprint $table) {
            if (! Schema::hasColumn('nomina', 'base_aportes_empleador')) {
                $table->decimal('base_aportes_empleador', 14, 2)->default(0)->after('total_deducciones');
            }
            if (! Schema::hasColumn('nomina', 'porcentaje_salud_empleador')) {
                $table->decimal('porcentaje_salud_empleador', 6, 3)->default(0)->after('base_aportes_empleador');
            }
            if (! Schema::hasColumn('nomina', 'porcentaje_pension_empleador')) {
                $table->decimal('porcentaje_pension_empleador', 6, 3)->default(0)->after('porcentaje_salud_empleador');
            }
            if (! Schema::hasColumn('nomina', 'porcentaje_arl')) {
                $table->decimal('porcentaje_arl', 6, 3)->default(0)->after('porcentaje_pension_empleador');
            }
            if (! Schema::hasColumn('nomina', 'porcentaje_sena')) {
                $table->decimal('porcentaje_sena', 6, 3)->default(0)->after('porcentaje_arl');
            }
            if (! Schema::hasColumn('nomina', 'porcentaje_icbf')) {
                $table->decimal('porcentaje_icbf', 6, 3)->default(0)->after('porcentaje_sena');
            }
            if (! Schema::hasColumn('nomina', 'porcentaje_caja_compensacion')) {
                $table->decimal('porcentaje_caja_compensacion', 6, 3)->default(0)->after('porcentaje_icbf');
            }
            if (! Schema::hasColumn('nomina', 'costo_salud_empleador')) {
                $table->decimal('costo_salud_empleador', 14, 2)->default(0)->after('porcentaje_caja_compensacion');
            }
            if (! Schema::hasColumn('nomina', 'costo_pension_empleador')) {
                $table->decimal('costo_pension_empleador', 14, 2)->default(0)->after('costo_salud_empleador');
            }
            if (! Schema::hasColumn('nomina', 'costo_arl')) {
                $table->decimal('costo_arl', 14, 2)->default(0)->after('costo_pension_empleador');
            }
            if (! Schema::hasColumn('nomina', 'costo_sena')) {
                $table->decimal('costo_sena', 14, 2)->default(0)->after('costo_arl');
            }
            if (! Schema::hasColumn('nomina', 'costo_icbf')) {
                $table->decimal('costo_icbf', 14, 2)->default(0)->after('costo_sena');
            }
            if (! Schema::hasColumn('nomina', 'costo_caja_compensacion')) {
                $table->decimal('costo_caja_compensacion', 14, 2)->default(0)->after('costo_icbf');
            }
            if (! Schema::hasColumn('nomina', 'costo_parafiscales')) {
                $table->decimal('costo_parafiscales', 14, 2)->default(0)->after('costo_caja_compensacion');
            }
            if (! Schema::hasColumn('nomina', 'costo_total_empleador')) {
                $table->decimal('costo_total_empleador', 14, 2)->default(0)->after('costo_parafiscales');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nomina', function (Blueprint $table) {
            $columns = [
                'costo_total_empleador',
                'costo_parafiscales',
                'costo_caja_compensacion',
                'costo_icbf',
                'costo_sena',
                'costo_arl',
                'costo_pension_empleador',
                'costo_salud_empleador',
                'porcentaje_caja_compensacion',
                'porcentaje_icbf',
                'porcentaje_sena',
                'porcentaje_arl',
                'porcentaje_pension_empleador',
                'porcentaje_salud_empleador',
                'base_aportes_empleador',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('nomina', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('configuracion_nomina', function (Blueprint $table) {
            $columns = [
                'porcentaje_caja_compensacion',
                'porcentaje_icbf',
                'porcentaje_sena',
                'porcentaje_arl',
                'porcentaje_pension_empleador',
                'porcentaje_salud_empleador',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('configuracion_nomina', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
