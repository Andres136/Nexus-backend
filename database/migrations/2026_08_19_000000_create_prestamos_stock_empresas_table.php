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
        Schema::create('prestamos_stock_empresas', function (Blueprint $table) {
            $table->id();

            // Movimiento de stock (descuento) que originó el préstamo
            $table->foreignId('movimiento_stock_id')
                  ->constrained('movimientos_stock')
                  ->onDelete('cascade');

            // Orden de compra que se estaba surtiendo
            $table->foreignId('orden_compra_id')
                  ->nullable()
                  ->constrained('orden__compras')
                  ->onDelete('cascade');

            $table->foreignId('producto_id')
                  ->constrained('products')
                  ->onDelete('cascade');

            $table->foreignId('bodega_id')
                  ->constrained('bodegas')
                  ->onDelete('cascade');

            // Registro de inventario del que realmente se descontó el stock
            $table->foreignId('inventario_id')
                  ->constrained('inventories')
                  ->onDelete('cascade');

            // Empresa dueña del stock que se usó (presta)
            $table->foreignId('empresa_prestamista_id')
                  ->constrained('empresas')
                  ->onDelete('cascade');

            // Empresa dueña de la orden de compra (recibe el préstamo)
            $table->foreignId('empresa_prestataria_id')
                  ->constrained('empresas')
                  ->onDelete('cascade');

            $table->decimal('cantidad', 12, 2);

            $table->boolean('compensado')->default(false);
            $table->timestamp('compensado_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestamos_stock_empresas');
    }
};
