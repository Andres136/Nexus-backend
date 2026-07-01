<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nomina_conceptos_contables', function (Blueprint $table) {
            if (! Schema::hasColumn('nomina_conceptos_contables', 'clasificacion_nomina')) {
                $table->enum('clasificacion_nomina', [
                    'salarial',
                    'pago_no_salarial',
                    'bonificacion',
                    'prestacion',
                    'aporte',
                    'deduccion',
                    'otro',
                ])->default('otro')->after('tipo');
            }

            if (! Schema::hasColumn('nomina_conceptos_contables', 'afecta_base_aportes')) {
                $table->boolean('afecta_base_aportes')->default(false)->after('naturaleza');
            }

            if (! Schema::hasColumn('nomina_conceptos_contables', 'afecta_prestaciones')) {
                $table->boolean('afecta_prestaciones')->default(false)->after('afecta_base_aportes');
            }

            if (! Schema::hasColumn('nomina_conceptos_contables', 'es_pago_no_salarial')) {
                $table->boolean('es_pago_no_salarial')->default(false)->after('afecta_prestaciones');
            }
        });

        Schema::table('nomina', function (Blueprint $table) {
            if (! Schema::hasColumn('nomina', 'pago_no_prestacional')) {
                $table->decimal('pago_no_prestacional', 14, 2)->default(0)->after('auxilio_transporte');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nomina', function (Blueprint $table) {
            if (Schema::hasColumn('nomina', 'pago_no_prestacional')) {
                $table->dropColumn('pago_no_prestacional');
            }
        });

        Schema::table('nomina_conceptos_contables', function (Blueprint $table) {
            foreach (['es_pago_no_salarial', 'afecta_prestaciones', 'afecta_base_aportes', 'clasificacion_nomina'] as $column) {
                if (Schema::hasColumn('nomina_conceptos_contables', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
