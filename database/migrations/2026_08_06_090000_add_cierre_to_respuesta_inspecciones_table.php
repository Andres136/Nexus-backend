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
        Schema::table('respuesta_inspecciones', function (Blueprint $table) {
            $table->string('foto_cierre')->nullable()->after('observaciones');
            $table->text('observaciones_cierre')->nullable()->after('foto_cierre');
            $table->timestamp('cerrado_en')->nullable()->after('observaciones_cierre');
            $table->foreignId('cerrado_por')->nullable()->after('cerrado_en')
                ->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('respuesta_inspecciones', function (Blueprint $table) {
            $table->dropForeign(['cerrado_por']);
            $table->dropColumn(['foto_cierre', 'observaciones_cierre', 'cerrado_en', 'cerrado_por']);
        });
    }
};
