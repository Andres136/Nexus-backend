<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nomina', function (Blueprint $table) {
            if (! Schema::hasColumn('nomina', 'estado_contable')) {
                $table->enum('estado_contable', ['borrador', 'aprobado', 'cerrado', 'exportado'])->default('borrador')->after('liquidada');
            }
            if (! Schema::hasColumn('nomina', 'aprobado_contabilidad_por')) {
                $table->foreignId('aprobado_contabilidad_por')->nullable()->after('estado_contable')->constrained('users');
            }
            if (! Schema::hasColumn('nomina', 'fecha_aprobacion_contable')) {
                $table->timestamp('fecha_aprobacion_contable')->nullable()->after('aprobado_contabilidad_por');
            }
            if (! Schema::hasColumn('nomina', 'fecha_cierre_contable')) {
                $table->timestamp('fecha_cierre_contable')->nullable()->after('fecha_aprobacion_contable');
            }
            if (! Schema::hasColumn('nomina', 'fecha_exportacion_contable')) {
                $table->timestamp('fecha_exportacion_contable')->nullable()->after('fecha_cierre_contable');
            }
        });

        Schema::table('contrataciones', function (Blueprint $table) {
            if (! Schema::hasColumn('contrataciones', 'centro_costo')) {
                $table->string('centro_costo', 80)->nullable()->after('empresa_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nomina', function (Blueprint $table) {
            if (Schema::hasColumn('nomina', 'aprobado_contabilidad_por')) {
                $table->dropConstrainedForeignId('aprobado_contabilidad_por');
            }
            foreach (['estado_contable', 'fecha_aprobacion_contable', 'fecha_cierre_contable', 'fecha_exportacion_contable'] as $column) {
                if (Schema::hasColumn('nomina', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('contrataciones', function (Blueprint $table) {
            if (Schema::hasColumn('contrataciones', 'centro_costo')) {
                $table->dropColumn('centro_costo');
            }
        });
    }
};
