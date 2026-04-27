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
        $table->foreignId('empresa_id')
    ->nullable() 
    ->after('proveedor_id')
    ->constrained('empresas')
    ->onDelete('cascade');
    $table->string('pdf_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factura_compras', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropColumn('empresa_id');
            $table->dropColumn('pdf_url');
        });
    }
};
