<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contrataciones', function (Blueprint $table) {
            foreach ([
                'salario_integral',
                'aplica_salud',
                'aplica_pension',
                'aplica_arl',
                'aplica_sena',
                'aplica_icbf',
                'aplica_caja_compensacion',
            ] as $column) {
                if (! Schema::hasColumn('contrataciones', $column)) {
                    $table->boolean($column)->default($column === 'salario_integral' ? false : true);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('contrataciones', function (Blueprint $table) {
            foreach ([
                'aplica_caja_compensacion',
                'aplica_icbf',
                'aplica_sena',
                'aplica_arl',
                'aplica_pension',
                'aplica_salud',
                'salario_integral',
            ] as $column) {
                if (Schema::hasColumn('contrataciones', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
