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
        Schema::table('hallazgo_novedades', function (Blueprint $table) {
            $table->unsignedInteger('orden')->default(0)->after('novedad_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hallazgo_novedades', function (Blueprint $table) {
            $table->dropColumn('orden');
        });
    }
};
