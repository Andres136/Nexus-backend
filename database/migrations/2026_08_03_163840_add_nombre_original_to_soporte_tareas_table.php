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
        Schema::table('soporte_tareas', function (Blueprint $table) {
            $table->string('nombre_original')->nullable()->after('soporte_tarea');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('soporte_tareas', function (Blueprint $table) {
            $table->dropColumn('nombre_original');
        });
    }
};
