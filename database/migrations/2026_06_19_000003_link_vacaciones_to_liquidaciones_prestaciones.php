<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('liquidaciones_prestaciones', function (Blueprint $table) {
            $table->foreignId('vacacion_id')
                ->nullable()
                ->after('contratacion_id')
                ->unique()
                ->constrained('vacaciones')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('liquidaciones_prestaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vacacion_id');
        });
    }
};
