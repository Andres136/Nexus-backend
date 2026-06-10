<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion_nomina', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracion_nomina', 'firma_talento_humano')) {
                $table->string('firma_talento_humano')->nullable()->after('porcentaje_pension_empleado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_nomina', function (Blueprint $table) {
            if (Schema::hasColumn('configuracion_nomina', 'firma_talento_humano')) {
                $table->dropColumn('firma_talento_humano');
            }
        });
    }
};
