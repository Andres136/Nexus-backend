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
        Schema::table('indicadores_procesos', function (Blueprint $table) {
            $table->enum('tipo_meta', ['mayor', 'menor'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indicadores_procesos', function (Blueprint $table) {
            $table->dropColumn('tipo_meta');
        });
    }
};
