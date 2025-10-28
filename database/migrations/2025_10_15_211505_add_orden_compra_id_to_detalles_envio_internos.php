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
        Schema::table('detalles_envio_internos', function (Blueprint $table) {
            $table->foreignId('orden_compra_id')
                ->nullable()
                ->constrained('orden_compra_proveedores')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalles_envio_internos', function (Blueprint $table) {
            $table->dropForeign(['orden_compra_id']);
            $table->dropColumn('orden_compra_id');
        });
    }
};
