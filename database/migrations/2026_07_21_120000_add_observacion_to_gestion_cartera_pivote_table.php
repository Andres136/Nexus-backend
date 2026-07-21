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
        Schema::table('gestion_cartera_pivote', function (Blueprint $table) {
            $table->string('observacion')->nullable()->after('fecha_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gestion_cartera_pivote', function (Blueprint $table) {
            $table->dropColumn('observacion');
        });
    }
};
