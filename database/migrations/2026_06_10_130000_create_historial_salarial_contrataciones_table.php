<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_salarial_contrataciones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('contratacion_id')->constrained('contrataciones')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('tipo_ajuste', ['salario_minimo', 'aumento_porcentual', 'aumento_manual', 'correccion', 'promocion', 'cambio_cargo'])->default('aumento_manual');
            $table->decimal('salario_anterior', 14, 2)->default(0);
            $table->decimal('salario_nuevo', 14, 2)->default(0);
            $table->decimal('auxilio_anterior', 14, 2)->default(0);
            $table->decimal('auxilio_nuevo', 14, 2)->default(0);
            $table->decimal('no_salarial_anterior', 14, 2)->default(0);
            $table->decimal('no_salarial_nuevo', 14, 2)->default(0);
            $table->decimal('porcentaje_aumento', 8, 4)->nullable();
            $table->date('fecha_vigencia');
            $table->string('motivo', 160)->nullable();
            $table->text('observacion')->nullable();
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['contratacion_id', 'fecha_vigencia'], 'hist_sal_contrato_fecha_idx');
            $table->index(['user_id', 'fecha_vigencia'], 'hist_sal_user_fecha_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_salarial_contrataciones');
    }
};
