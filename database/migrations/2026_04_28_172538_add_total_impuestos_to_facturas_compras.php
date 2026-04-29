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
        Schema::table('factura_compras', function (Blueprint $table) {
            $table->decimal('total_impuestos', 15, 2)->default(0)->after('total');
            $table->decimal('total_gastos', 15, 2)->default(0)->after('total_impuestos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas_compras', function (Blueprint $table) {
            $table->dropColumn('total_impuestos');
            $table->dropColumn('total_gastos');
        });
    }
};
