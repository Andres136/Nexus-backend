<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Paso 1: renombrar columnas de horas (quitamos el sufijo _id confuso)
        Schema::table('nomina', function (Blueprint $table) {
            $table->renameColumn('horas_normales_trabajada_id', 'horas_normales');
            $table->renameColumn('horas_extras_nocturnas_id',   'horas_extras_nocturnas');
            $table->renameColumn('horas_extras_diurna_id',      'horas_extras_diurnas');
            $table->renameColumn('horas_festivas_id',           'horas_festivas');
            $table->renameColumn('horas_nocturnas_festivas_id', 'horas_nocturnas_festivas');
        });

        // Paso 2: cambiar tipo de integer a decimal y agregar todos los campos financieros
        Schema::table('nomina', function (Blueprint $table) {
            // Cambiar tipo de horas a decimal
            $table->decimal('horas_normales',          8, 2)->default(0)->change();
            $table->decimal('horas_extras_nocturnas',  8, 2)->default(0)->change();
            $table->decimal('horas_extras_diurnas',    8, 2)->default(0)->change();
            $table->decimal('horas_festivas',          8, 2)->default(0)->change();
            $table->decimal('horas_nocturnas_festivas',8, 2)->default(0)->change();

            // Período liquidado
            $table->date('periodo_inicio')->nullable()->after('uuid');
            $table->date('periodo_fin')->nullable()->after('periodo_inicio');

            // Vínculo con el contrato activo en el momento de liquidar
            $table->foreignId('contratacion_id')
                  ->nullable()
                  ->after('jornada_laboral_id')
                  ->constrained('contrataciones');

            // Snapshot de tarifas al momento de calcular
            $table->decimal('valor_hora_normal',          12, 2)->default(0)->after('contratacion_id');
            $table->decimal('valor_hora_nocturna',        12, 2)->default(0);
            $table->decimal('valor_hora_dominical',       12, 2)->default(0);
            $table->decimal('valor_hora_dominical_extra', 12, 2)->default(0);

            // Devengados
            $table->decimal('salario_base_devengado',         12, 2)->default(0);
            $table->decimal('auxilio_transporte',             12, 2)->default(0);
            $table->decimal('valor_horas_normales',           12, 2)->default(0);
            $table->decimal('valor_horas_extras_nocturnas',   12, 2)->default(0);
            $table->decimal('valor_horas_extras_diurnas',     12, 2)->default(0);
            $table->decimal('valor_horas_festivas',           12, 2)->default(0);
            $table->decimal('valor_horas_nocturnas_festivas', 12, 2)->default(0);
            $table->decimal('total_devengado',                12, 2)->default(0);

            // Deducciones
            $table->decimal('deduccion_salud',              12, 2)->default(0);
            $table->decimal('deduccion_pension',            12, 2)->default(0);
            $table->decimal('total_descuentos_adicionales', 12, 2)->default(0);
            $table->decimal('total_deducciones',            12, 2)->default(0);

            // Resultado final
            $table->decimal('salario_neto', 12, 2)->default(0);

            // Estado de liquidación
            $table->boolean('liquidada')->default(false);
            $table->timestamp('fecha_liquidacion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('nomina', function (Blueprint $table) {
            $table->dropForeign(['contratacion_id']);
            $table->dropColumn([
                'periodo_inicio', 'periodo_fin', 'contratacion_id',
                'valor_hora_normal', 'valor_hora_nocturna', 'valor_hora_dominical', 'valor_hora_dominical_extra',
                'salario_base_devengado', 'auxilio_transporte',
                'valor_horas_normales', 'valor_horas_extras_nocturnas', 'valor_horas_extras_diurnas',
                'valor_horas_festivas', 'valor_horas_nocturnas_festivas', 'total_devengado',
                'deduccion_salud', 'deduccion_pension', 'total_descuentos_adicionales', 'total_deducciones',
                'salario_neto', 'liquidada', 'fecha_liquidacion',
            ]);
            $table->renameColumn('horas_normales',           'horas_normales_trabajada_id');
            $table->renameColumn('horas_extras_nocturnas',   'horas_extras_nocturnas_id');
            $table->renameColumn('horas_extras_diurnas',     'horas_extras_diurna_id');
            $table->renameColumn('horas_festivas',           'horas_festivas_id');
            $table->renameColumn('horas_nocturnas_festivas', 'horas_nocturnas_festivas_id');
        });
    }
};
