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
        Schema::table('orden__compra__detalles', function (Blueprint $table) {
            $table->text('observaciones_calidad')->nullable();
            $table->foreignId('observaciones_calidad_usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('observaciones_calidad_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden__compra__detalles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('observaciones_calidad_usuario_id');
            $table->dropColumn(['observaciones_calidad', 'observaciones_calidad_at']);
        });
    }
};
