<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('carpetas', function (Blueprint $table) {
            // Eliminar unique global en nombre — permite carpetas con el mismo nombre en distintos padres
            $table->dropUnique(['nombre']);

            $table->foreignId('parent_id')
                ->nullable()
                ->after('id')
                ->constrained('carpetas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('carpetas', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
            $table->unique('nombre');
        });
    }
};
