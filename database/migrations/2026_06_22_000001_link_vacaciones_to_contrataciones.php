<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacaciones', function (Blueprint $table) {
            $table->foreignId('contratacion_id')
                ->nullable()
                ->after('user_id')
                ->constrained('contrataciones')
                ->nullOnDelete();

            $table->index(['user_id', 'contratacion_id', 'status'], 'vacaciones_saldo_index');
        });
    }

    public function down(): void
    {
        Schema::table('vacaciones', function (Blueprint $table) {
            $table->dropIndex('vacaciones_saldo_index');
            $table->dropConstrainedForeignId('contratacion_id');
        });
    }
};
