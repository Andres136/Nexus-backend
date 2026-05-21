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
        Schema::create('productos_no_conformes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('comercial_id')->constrained('users');
            $table->foreignId('producto_id')->nullable()->constrained('products');
            $table->foreignId('orden_compra_id')->nullable()->constrained('orden__compras');

            $table->date('fecha_reporte');
            $table->integer('cantidad_afectada')->default(1);

            $table->text('descripcion_inicial');
            $table->string('tipo_falla')->nullable();

            $table->foreignId('estado_id')->constrained('estados');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos_no_conformes');
    }
};
