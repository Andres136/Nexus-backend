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
        Schema::table('puck', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('puck')->nullOnDelete();
            $table->string('nivel', 20)->nullable()->after('numero');
            $table->string('naturaleza', 10)->nullable()->after('nivel');
            $table->text('descripcion')->nullable()->after('naturaleza');
            $table->text('dinamica')->nullable()->after('descripcion');
            $table->boolean('permite_movimiento')->default(true)->after('dinamica');
            $table->boolean('activo')->default(true)->after('permite_movimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('puck', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn(['nivel', 'naturaleza', 'descripcion', 'dinamica', 'permite_movimiento', 'activo']);
            $table->unique('nombre');
        });
    }
};
