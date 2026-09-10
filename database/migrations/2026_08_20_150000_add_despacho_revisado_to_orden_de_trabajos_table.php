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
        Schema::table('orden_de_trabajos', function (Blueprint $table) {
            $table->timestamp('despacho_revisado_at')->nullable();
            $table->foreignId('despacho_revisado_por')
                ->nullable()
                ->after('despacho_revisado_at')
                ->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden_de_trabajos', function (Blueprint $table) {
            $table->dropForeign(['despacho_revisado_por']);
            $table->dropColumn(['despacho_revisado_at', 'despacho_revisado_por']);
        });
    }
};
