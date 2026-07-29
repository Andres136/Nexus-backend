<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capacitacion_acta_envios', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->after('user_id')->constrained('empresas')->nullOnDelete();
            $table->string('empresa_nombre')->nullable()->after('empresa_id');
            $table->index(['acta_id', 'empresa_id']);
        });
    }

    public function down(): void
    {
        Schema::table('capacitacion_acta_envios', function (Blueprint $table) {
            $table->dropIndex(['acta_id', 'empresa_id']);
            $table->dropConstrainedForeignId('empresa_id');
            $table->dropColumn('empresa_nombre');
        });
    }
};
