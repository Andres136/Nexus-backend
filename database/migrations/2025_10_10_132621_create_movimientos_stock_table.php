<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('movimientos_stock', function (Blueprint $table) {
            $table->id();
                 // Relación a la orden de trabajo
            $table->foreignId('orden_trabajo_id')
                  ->nullable()
                  ->constrained('orden_de_trabajos')
                  ->onDelete('cascade');

            // Relación a la orden de compra (opcional, para trazabilidad híbrida)
            $table->foreignId('orden_compra_id')
                  ->nullable()
                  ->constrained('orden__compras')
                  ->onDelete('cascade');

            // Producto
            $table->foreignId('producto_id')
                  ->constrained('products')
                  ->onDelete('cascade');

            // Usuario responsable del movimiento
            $table->foreignId('usuario_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // Información del movimiento
            $table->enum('tipo', ['descuento', 'entrada', 'ajuste'])->default('descuento');
            $table->integer('cantidad');
            $table->json('detalle')->nullable(); // bodegas, equivalentes, etc.
            $table->string('razon')->nullable();

            // PDF generado para el movimiento
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_stock');
    }
};
