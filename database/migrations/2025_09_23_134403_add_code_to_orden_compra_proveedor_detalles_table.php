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
            $table->string('code', 100)->nullable()->after('id'); // o después del campo que prefieras
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden_compra_proveedor_detalles', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
