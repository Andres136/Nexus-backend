<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liquidaciones_retiro', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('contratacion_id')->unique()->constrained('contrataciones');
            $table->foreignId('nomina_id')->nullable()->constrained('nomina');
            $table->foreignId('jornada_laboral_id')->constrained('jornada_laborals');

            $table->date('fecha_retiro');
            $table->string('motivo_retiro', 50);
            $table->date('periodo_salario_inicio')->nullable();
            $table->date('periodo_salario_fin')->nullable();

            $table->unsignedInteger('dias_contrato');
            $table->unsignedInteger('dias_cesantias');
            $table->unsignedInteger('dias_prima');
            $table->decimal('dias_vacaciones_pendientes', 10, 4);

            $table->decimal('base_cesantias', 14, 2);
            $table->decimal('base_prima', 14, 2);
            $table->decimal('base_vacaciones', 14, 2);

            $table->decimal('salario_pendiente', 14, 2)->default(0);
            $table->decimal('pago_no_prestacional', 14, 2)->default(0);
            $table->decimal('comisiones_pendientes', 14, 2)->default(0);
            $table->decimal('cesantias', 14, 2)->default(0);
            $table->decimal('intereses_cesantias', 14, 2)->default(0);
            $table->decimal('prima_servicios', 14, 2)->default(0);
            $table->decimal('vacaciones', 14, 2)->default(0);
            $table->decimal('indemnizacion', 14, 2)->default(0);
            $table->decimal('total_devengado', 14, 2);
            $table->decimal('deducciones_nomina', 14, 2)->default(0);
            $table->decimal('deducciones_comisiones_pendientes', 14, 2)->default(0);
            $table->decimal('deducciones_finales', 14, 2)->default(0);
            $table->decimal('total_deducciones', 14, 2);
            $table->decimal('neto_pagar', 14, 2);

            $table->json('detalle_calculo')->nullable();
            $table->timestamp('fecha_liquidacion');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('comisiones', function (Blueprint $table) {
            $table->foreignId('liquidacion_retiro_id')
                ->nullable()
                ->after('nomina_id')
                ->constrained('liquidaciones_retiro');
        });
    }

    public function down(): void
    {
        Schema::table('comisiones', function (Blueprint $table) {
            $table->dropForeign(['liquidacion_retiro_id']);
            $table->dropColumn('liquidacion_retiro_id');
        });

        Schema::dropIfExists('liquidaciones_retiro');
    }
};
