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
        Schema::table('factura_pagos', function (Blueprint $table) {
        $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
        $table->date('fecha_pago')->nullable();
        $table->text('observaciones')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factura_pagos', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->dropColumn('fecha_pago');
            $table->dropColumn('observaciones');
        });
    }
};
