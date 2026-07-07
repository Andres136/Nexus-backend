<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requerimientos_compra', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('codigo')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sede_id')->constrained('sedes')->restrictOnDelete();
            $table->foreignId('bodega_id')->nullable()->constrained('bodegas')->nullOnDelete();
            $table->foreignId('orden_trabajo_id')->nullable()->constrained('orden_de_trabajos')->nullOnDelete();
            $table->string('prioridad', 20)->default('normal');
            $table->string('estado', 30)->default('solicitado');
            $table->date('fecha_requerida')->nullable();
            $table->dateTime('fecha_solicitud');
            $table->text('observacion')->nullable();
            $table->foreignId('rechazado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rechazado_at')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->foreignId('analizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('analizado_at')->nullable();
            $table->foreignId('orden_compra_id')->nullable()->constrained('orden_compra_proveedores')->nullOnDelete();
            $table->foreignId('generado_oc_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generado_oc_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['estado', 'fecha_solicitud']);
            $table->index(['sede_id', 'bodega_id']);
        });

        Schema::create('requerimiento_compra_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requerimiento_compra_id')->constrained('requerimientos_compra')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('products')->nullOnDelete();
            $table->decimal('cantidad_solicitada', 12, 2);
            $table->decimal('cantidad_aprobada', 12, 2)->nullable();
            $table->decimal('cantidad_comprada', 12, 2)->nullable();
            $table->decimal('costo_estimado', 15, 2)->nullable();
            $table->foreignId('proveedor_sugerido_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->string('referencia_sugerida')->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

        Schema::create('requerimiento_compra_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requerimiento_compra_id')->constrained('requerimientos_compra')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo_evento', 40);
            $table->string('estado_anterior', 30)->nullable();
            $table->string('estado_nuevo', 30)->nullable();
            $table->text('comentario')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requerimiento_compra_eventos');
        Schema::dropIfExists('requerimiento_compra_detalles');
        Schema::dropIfExists('requerimientos_compra');
    }
};
