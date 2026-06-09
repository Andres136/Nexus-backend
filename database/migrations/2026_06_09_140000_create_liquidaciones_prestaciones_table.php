<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liquidaciones_prestaciones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('contratacion_id')->constrained('contrataciones');
            $table->enum('tipo', ['prima', 'cesantias', 'vacaciones_compensadas']);
            $table->date('periodo_inicio');
            $table->date('periodo_fin');
            $table->integer('dias_liquidados');
            $table->decimal('salario_mensual', 14, 4);
            $table->decimal('auxilio_transporte', 14, 4)->default(0);
            $table->decimal('promedio_variable', 14, 4)->default(0);
            $table->decimal('base_calculo', 14, 4);
            $table->decimal('valor_calculado', 14, 2);
            $table->decimal('intereses_cesantias', 14, 2)->default(0);
            $table->decimal('dias_vacaciones', 10, 4)->nullable();
            $table->decimal('total_liquidado', 14, 2);
            $table->json('detalle_calculo')->nullable();
            $table->timestamp('fecha_liquidacion')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'tipo']);
            $table->index('periodo_inicio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidaciones_prestaciones');
    }
};
