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

        // 🔹 hacer nullable bodega_id
        $table->foreignId('bodega_id')->nullable()->change();

        // 🔹 agregar puck_id nullable
        $table->foreignId('puck_id')
            ->after('producto_id')
            ->nullable()
            ->constrained('puck')
            ->onDelete('restrict');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalles_factura_compra', function (Blueprint $table) {

        // 🔹 quitar FK primero
        $table->dropForeign(['puck_id']);
        $table->dropColumn('puck_id');

        // 🔹 volver bodega_id NOT NULL
        $table->foreignId('bodega_id')->nullable(false)->change();
    });
    }
};
