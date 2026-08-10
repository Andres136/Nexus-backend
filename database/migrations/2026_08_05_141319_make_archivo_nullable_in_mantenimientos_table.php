<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sin doctrine/dbal instalado: ALTER directo en vez de Blueprint::change().
        DB::statement('ALTER TABLE mantenimientos MODIFY archivo VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE mantenimientos SET archivo = '' WHERE archivo IS NULL");
        DB::statement('ALTER TABLE mantenimientos MODIFY archivo VARCHAR(255) NOT NULL');
    }
};
