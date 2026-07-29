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
        Schema::table('novedad_diaria', function (Blueprint $table) {
            $table->string('numero_no_conformidad')->nullable()->after('descripcion');
            $table->text('correccion')->nullable()->after('numero_no_conformidad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('novedad_diaria', function (Blueprint $table) {
            $table->dropColumn(['numero_no_conformidad', 'correccion']);
        });
    }
};
