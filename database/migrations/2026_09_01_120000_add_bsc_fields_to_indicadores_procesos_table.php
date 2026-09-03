<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campos del Cuadro de Mando Integral (BSC) sobre el catálogo de
     * indicadores que ya existe. Todo nullable: un indicador sin clasificar
     * sigue funcionando igual que hoy, solo que no aparece en la vista BSC
     * hasta que un admin le asigna perspectiva.
     */
    public function up(): void
    {
        Schema::table('indicadores_procesos', function (Blueprint $table) {
            // clave de bsc_perspectivas (financiera|cliente|procesos|aprendizaje)
            $table->string('perspectiva')->nullable()->after('tipo_meta');
            // objetivo estratégico del mapa (texto libre, se agrupa por él)
            $table->string('objetivo_estrategico')->nullable()->after('perspectiva');
            // unidad de presentación: %, $, dias, ratio
            $table->string('unidad', 20)->nullable()->after('objetivo_estrategico');
            // identifica el resolver automático; null => captura manual (comportamiento actual)
            $table->string('calculo_key')->nullable()->after('unidad');
            // orden dentro de su perspectiva en el dashboard
            $table->unsignedSmallInteger('orden')->default(0)->after('calculo_key');
        });
    }

    public function down(): void
    {
        Schema::table('indicadores_procesos', function (Blueprint $table) {
            $table->dropColumn([
                'perspectiva',
                'objetivo_estrategico',
                'unidad',
                'calculo_key',
                'orden',
            ]);
        });
    }
};
