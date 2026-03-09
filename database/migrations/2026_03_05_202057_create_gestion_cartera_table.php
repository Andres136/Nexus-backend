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
        Schema::create('gestion_cartera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('numero_factura');
            $table->foreignId('user_comercial_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->decimal('valor_total', 15, 2);
            $table->decimal('saldo_pendiente', 15, 2)->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->text('observaciones')->nullable();
            $table->date('fecha_factura')->nullable();
            $table->string('estado')->default('pendiente');
            $table->integer('dias_credito')->nullable();
            $table->decimal('base', 15, 2)->nullable();
            $table->decimal('iva', 15, 2)->nullable();
            $table->decimal('rete_renta', 15, 2)->nullable();
            $table->decimal('rete_ica', 15, 2)->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gestion_cartera');
    }
};
