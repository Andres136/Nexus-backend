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
        Schema::create('orden__compra__detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('orden__compras')->onDelete('cascade');
            $table->decimal('largo_cm', 10, 2)->nullable();
            $table->decimal('ancho_cm', 10, 2)->nullable();
            $table->float('calibre')->nullable();
            $table->integer('cantidad')->default(0);
            $table->integer('cantidad_enviada')->default(0);
            $table->integer('faltantes')->default(0);
            $table->decimal('valor_unitario', 10, 2)->nullable();
            $table->float('peso_bolsa')->nullable();
            $table->integer('numero_bolsas')->default(0);
            $table->string('cliente_clb', 255)->nullable();
            $table->decimal('cantidad_requerida_kg', 10, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->decimal('valor_total', 10, 2)->nullable();
            $table->text('descripcion', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
            Schema::table('orden__compra__detalles', function (Blueprint $table) {
            $table->dropForeign(['orden_compra_id']);
        });
        Schema::dropIfExists('orden__compra__detalles');
    }
};
