<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nomina', function (Blueprint $table) {
            $table->decimal('dias_salario', 10, 4)->nullable()->after('periodo_fin');
            $table->decimal('dias_vacaciones_ordinarias', 10, 4)->default(0)->after('dias_salario');
        });
    }

    public function down(): void
    {
        Schema::table('nomina', function (Blueprint $table) {
            $table->dropColumn(['dias_salario', 'dias_vacaciones_ordinarias']);
        });
    }
};
