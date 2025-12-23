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
            $table->unsignedBigInteger('empresa_id')->nullable()->after('id');
            $table->string('cliente_documento')->nullable()->after('empresa_id');
            $table->string('code',30)->unique()->nullable()->after('cliente_documento');
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('set null');
            $table->timestamp('documento_revisado_at')->nullable()->after('cliente_documento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden__compras', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropColumn(['empresa_id', 'cliente_documento', 'documento_revisado_at', 'code']);
        });
    }
};
