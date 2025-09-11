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
        Schema::table('orden__compras', function (Blueprint $table) {
            $table->timestamp('fecha_despacho')->nullable()->after('fecha_entrega');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden__compras', function (Blueprint $table) {
            $table->dropColumn('fecha_despacho');
        });
    }
};
