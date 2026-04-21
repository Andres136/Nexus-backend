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
        Schema::table('orden__compra__detalles', function (Blueprint $table) {
                $table->decimal('cantidad_ejecutada_kg', 10, 2)
              ->default(0)
              ->after('cantidad_requerida_kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden_compra_detalles', function (Blueprint $table) {
            $table->dropColumn('cantidad_ejecutada_kg');
        });
    }
};
