<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_prestamos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('descuento_id')->nullable()->constrained('descuentos')->nullOnDelete();
            $table->foreignId('gestionado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('monto_solicitado', 12, 2);
            $table->integer('numero_cuotas_solicitadas')->nullable();
            $table->enum('frecuencia_pago_solicitada', ['quincenal', 'mensual'])->default('quincenal');
            $table->text('motivo')->nullable();
            $table->enum('status', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
            $table->decimal('monto_aprobado', 12, 2)->nullable();
            $table->decimal('tasa_interes_porcentaje', 5, 2)->default(0);
            $table->decimal('valor_interes', 12, 2)->default(0);
            $table->decimal('total_a_descontar', 12, 2)->nullable();
            $table->integer('numero_cuotas_aprobadas')->nullable();
            $table->decimal('valor_cuota_aprobada', 12, 2)->nullable();
            $table->enum('frecuencia_pago_aprobada', ['quincenal', 'mensual'])->nullable();
            $table->date('inicio_descuento')->nullable();
            $table->text('observacion_nomina')->nullable();
            $table->timestamp('fecha_gestion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_prestamos');
    }
};
