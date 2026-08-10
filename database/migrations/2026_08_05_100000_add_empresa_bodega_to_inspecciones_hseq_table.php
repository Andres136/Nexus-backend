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
        Schema::table('inspecciones_hseq', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->after('sede_id')
                ->references('id')->on('empresas')->onDelete('set null');
            $table->foreignId('bodega_id')->nullable()->after('empresa_id')
                ->references('id')->on('bodegas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inspecciones_hseq', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropForeign(['bodega_id']);
            $table->dropColumn(['empresa_id', 'bodega_id']);
        });
    }
};
