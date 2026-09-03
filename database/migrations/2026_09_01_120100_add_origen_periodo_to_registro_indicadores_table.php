<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distingue los registros que teclea el responsable (manual) de los que
     * calcula el motor BSC a partir de los módulos del sistema (automatico).
     *
     * `fecha` se mantiene como la fecha real en que se registró el valor
     * (la fija el usuario en el formulario manual, y el motor la fija a la
     * fecha en que corre el snapshot). `periodo` es el mes al que corresponde
     * ese valor (Y-m), lo que permite agrupar y congelar meses cerrados sin
     * depender de que `fecha` caiga exactamente dentro del mes.
     */
    public function up(): void
    {
        Schema::table('registro_indicadores', function (Blueprint $table) {
            $table->enum('origen', ['manual', 'automatico'])->default('manual')->after('valor');
            $table->char('periodo', 7)->nullable()->after('origen'); // 'YYYY-MM'
            // trazabilidad del cálculo automático
            $table->decimal('numerador', 15, 2)->nullable()->after('periodo');
            $table->decimal('denominador', 15, 2)->nullable()->after('numerador');

            $table->index(['indicador_id', 'periodo']);

            // Los registros automáticos no los crea un usuario.
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('registro_indicadores', function (Blueprint $table) {
            $table->dropIndex(['indicador_id', 'periodo']);
            $table->dropColumn(['origen', 'periodo', 'numerador', 'denominador']);
        });
    }
};
