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
                $table->string('estado_contable', 30)->nullable()->after('fecha_liquidacion');
            }

            if (! Schema::hasColumn('nomina', 'fecha_aprobacion_contable')) {
                $table->timestamp('fecha_aprobacion_contable')->nullable()->after('estado_contable');
            }

            if (! Schema::hasColumn('nomina', 'fecha_cierre_contable')) {
                $table->timestamp('fecha_cierre_contable')->nullable()->after('fecha_aprobacion_contable');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nomina', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('nomina', 'fecha_cierre_contable')) {
                $columns[] = 'fecha_cierre_contable';
            }

            if (Schema::hasColumn('nomina', 'fecha_aprobacion_contable')) {
                $columns[] = 'fecha_aprobacion_contable';
            }

            if (Schema::hasColumn('nomina', 'estado_contable')) {
                $columns[] = 'estado_contable';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
