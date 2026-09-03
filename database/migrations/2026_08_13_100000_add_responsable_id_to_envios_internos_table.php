<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('envios_internos', function (Blueprint $table) {
            $table->foreignId('responsable_id')
                ->nullable()
                ->after('usuario_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('envios_internos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsable_id');
        });
    }
};
