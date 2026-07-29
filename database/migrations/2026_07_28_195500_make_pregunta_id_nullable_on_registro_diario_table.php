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
        // Sin doctrine/dbal en este proyecto, así que se usa SQL directo en vez
        // de Blueprint::change(). MODIFY COLUMN conserva la FK existente.
        DB::statement('ALTER TABLE registro_diario MODIFY pregunta_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE registro_diario MODIFY pregunta_id BIGINT UNSIGNED NOT NULL');
    }
};
