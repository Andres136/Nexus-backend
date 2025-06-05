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
        Schema::table('orden__compras', function (Blueprint $table) {

            // Add the foreign key column for sede_id
            $table->foreignId('sede_id')->nullable()->constrained('sedes')->onDelete('set null');

           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void

    {
        // Drop the foreign key constraints first
        Schema::table('orden__compras', function (Blueprint $table) {
            $table->dropForeign(['sede_id']);
        });
        Schema::table('orden__compras', function (Blueprint $table) {
            //
        });
    }
};
