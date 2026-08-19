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
        Schema::create('kardex_movimientos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventario_id')
                  ->constrained('inventories')
                  ->onDelete('cascade');

            // Salida ya auditada en movimientos_stock (descuentos, etc.)
            $table->foreignId('movimiento_stock_id')
                  ->nullable()
                  ->constrained('movimientos_stock')
                  ->nullOnDelete();

            // Entrada que vino de una línea de Factura de Compra
            $table->foreignId('factura_compra_detalle_id')
                  ->nullable()
                  ->constrained('detalles_factura_compra')
                  ->nullOnDelete();

            $table->enum('tipo', ['entrada', 'salida']);

            $table->decimal('cantidad', 12, 2);
            $table->decimal('costo_unitario', 14, 4);
            $table->decimal('costo_total', 14, 2);

            // Saldo corriente después de esta línea (permite leer el kardex
            // sin recalcular todo el historial cada vez).
            $table->decimal('saldo_cantidad', 12, 2);
            $table->decimal('saldo_costo_unitario', 14, 4);
            $table->decimal('saldo_costo_total', 14, 2);

            $table->foreignId('usuario_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kardex_movimientos');
    }
};
