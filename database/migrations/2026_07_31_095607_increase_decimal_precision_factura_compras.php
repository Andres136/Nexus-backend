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
        Schema::table('detalles_factura_compra', function (Blueprint $table) {
            $table->decimal('cantidad', 15, 4)->change();
            $table->decimal('precio_unitario', 15, 4)->change();
            $table->decimal('total', 15, 4)->change();
        });

        Schema::table('factura_compras', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 4)->change();
            $table->decimal('total', 15, 4)->change();
            $table->decimal('total_impuestos', 15, 4)->change();
            $table->decimal('total_gastos', 15, 4)->change();
            $table->decimal('saldo_pendiente', 15, 4)->nullable()->change();
        });

        Schema::table('factura_compra_impuestos', function (Blueprint $table) {
            $table->decimal('monto', 15, 4)->change();
        });

        Schema::table('detalle_factura_impuestos', function (Blueprint $table) {
            $table->decimal('monto', 15, 4)->change();
        });

        Schema::table('factura_pagos', function (Blueprint $table) {
            $table->decimal('monto', 15, 4)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalles_factura_compra', function (Blueprint $table) {
            $table->decimal('cantidad', 15, 2)->change();
            $table->decimal('precio_unitario', 15, 2)->change();
            $table->decimal('total', 15, 2)->change();
        });

        Schema::table('factura_compras', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 2)->change();
            $table->decimal('total', 15, 2)->change();
            $table->decimal('total_impuestos', 15, 2)->change();
            $table->decimal('total_gastos', 15, 2)->change();
            $table->decimal('saldo_pendiente', 15, 2)->nullable()->change();
        });

        Schema::table('factura_compra_impuestos', function (Blueprint $table) {
            $table->decimal('monto', 15, 2)->change();
        });

        Schema::table('detalle_factura_impuestos', function (Blueprint $table) {
            $table->decimal('monto', 15, 2)->change();
        });

        Schema::table('factura_pagos', function (Blueprint $table) {
            $table->decimal('monto', 15, 2)->change();
        });
    }
};
