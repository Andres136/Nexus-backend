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
        Schema::table('movimientos_stock', function (Blueprint $table) {
            
            // 🔥 1. Quitar la llave foránea
            $table->dropForeign(['producto_id']);

            // 🔥 2. Cambiar producto_id a JSON
            $table->json('producto_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimientos_stock', function (Blueprint $table) {
               // REVERTIR A BIGINT
            $table->unsignedBigInteger('producto_id')->nullable(false)->change();

            // Recrear foreign key
            $table->foreign('producto_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');
        });
    }
};
