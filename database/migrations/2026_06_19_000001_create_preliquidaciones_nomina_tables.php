<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preliquidaciones_nomina', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('contratacion_id')->constrained('contrataciones');
            $table->foreignId('jornada_laboral_id')->constrained('jornada_laborals');
            $table->foreignId('descuento_id')->nullable()->constrained('descuentos')->nullOnDelete();
            $table->date('periodo_inicio');
            $table->date('periodo_fin');
            $table->enum('estado', ['borrador', 'en_revision', 'aprobada', 'liquidada', 'rechazada'])
                ->default('borrador');
            $table->json('calculo_original');
            $table->json('calculo_ajustado');
            $table->decimal('total_devengado_original', 14, 2);
            $table->decimal('total_deducciones_original', 14, 2);
            $table->decimal('salario_neto_original', 14, 2);
            $table->decimal('total_devengado_ajustado', 14, 2);
            $table->decimal('total_deducciones_ajustado', 14, 2);
            $table->decimal('salario_neto_ajustado', 14, 2);
            $table->foreignId('generado_por')->constrained('users');
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_revision')->nullable();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->text('observacion_revision')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'periodo_inicio', 'periodo_fin']);
        });

        Schema::create('preliquidacion_nomina_ajustes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('preliquidacion_id')
                ->constrained('preliquidaciones_nomina')
                ->cascadeOnDelete();
            $table->enum('tipo', ['devengo', 'deduccion']);
            $table->string('concepto', 255);
            $table->decimal('valor', 14, 2);
            $table->boolean('afecta_base_aportes')->default(false);
            $table->text('motivo');
            $table->foreignId('creado_por')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('nomina', function (Blueprint $table) {
            $table->foreignId('preliquidacion_id')
                ->nullable()
                ->after('contratacion_id')
                ->constrained('preliquidaciones_nomina')
                ->nullOnDelete();
            $table->foreignId('liquidado_por')
                ->nullable()
                ->after('fecha_liquidacion')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('nomina', function (Blueprint $table) {
            $table->dropConstrainedForeignId('liquidado_por');
            $table->dropConstrainedForeignId('preliquidacion_id');
        });

        Schema::dropIfExists('preliquidacion_nomina_ajustes');
        Schema::dropIfExists('preliquidaciones_nomina');
    }
};
