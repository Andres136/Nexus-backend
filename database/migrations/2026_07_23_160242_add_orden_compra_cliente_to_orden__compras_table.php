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
            $table->string('orden_compra_cliente')->nullable()->after('cliente_documento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden__compras', function (Blueprint $table) {
            $table->dropColumn('orden_compra_cliente');
        });
    }
};
