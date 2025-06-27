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
        Schema::table('entregas_proveedor', function (Blueprint $table) {
             Schema::table('entregas_proveedor', function (Blueprint $table) {
            $table->timestamp('fecha_entrega')->nullable()->change();
        });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entregas_proveedor', function (Blueprint $table) {
            $table->timestamp('fecha_entrega')->nullable(false)->change();
        });
    }
};
