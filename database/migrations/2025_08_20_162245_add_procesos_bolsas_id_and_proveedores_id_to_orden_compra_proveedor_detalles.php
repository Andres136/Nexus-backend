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
        Schema::table('orden_compra_proveedor_detalles', function (Blueprint $table) {
            $table->unsignedBigInteger('proceso_bolsas_id')->nullable();
            $table->unsignedBigInteger('proveedor_id')->nullable();

            $table->foreign('proceso_bolsas_id')->references('id')->on('proceso_bolsas')->onDelete('set null');
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden_compra_proveedor_detalles', function (Blueprint $table) {
            $table->dropForeign(['proceso_bolsas_id']);
            $table->dropForeign(['proveedor_id']);
            $table->dropColumn(['proceso_bolsas_id', 'proveedor_id']);
        });
    }
};
