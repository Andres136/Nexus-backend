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
        Schema::create('cotizacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->onDelete('cascade');
            $table->unsignedInteger('item');
            $table->decimal('largo_cm', 8, 2)->nullable();
            $table->decimal('ancho_cm', 8, 2)->nullable();
            $table->decimal('calibre', 8, 2)->nullable();
            $table->decimal('peso_bolsa', 8, 2)->default(0);
            $table->integer('numero_bolsas')->default(0);
            $table->integer('cantidad')->default(0);
            $table->decimal('precio_total', 12, 2)->default(0); // ingresado por el usuario
            $table->decimal('valor_unitario', 12, 2)->default(0); // calculado = precio_total / numero_bolsas
            $table->decimal('valor_total', 12, 2)->default(0); // calculado = cantidad * valor_unitario * 1.19
            $table->decimal('cantidad_requerida_kg', 10, 2)->default(0);
            $table->string('descripcion')->nullable();
            $table->string('cliente_clb')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key constraints first
        Schema::table('cotizacion_detalles', function (Blueprint $table) {
            $table->dropForeign(['cotizacion_id']);
        });
        Schema::dropIfExists('cotizacion_detalles');
    }
};
