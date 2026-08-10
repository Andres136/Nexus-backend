<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_events', function (Blueprint $table) {
            $table->string('tipo', 20)->default('entrega')->after('id');
            $table->foreignId('proveedor_id')->nullable()->after('orden_id')->constrained('proveedores')->nullOnDelete();
        });

        DB::statement('ALTER TABLE delivery_events MODIFY orden_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE delivery_events MODIFY cantidad DECIMAL(10,2) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE delivery_events MODIFY cantidad DECIMAL(10,2) NOT NULL');
        DB::statement('ALTER TABLE delivery_events MODIFY orden_id BIGINT UNSIGNED NOT NULL');

        Schema::table('delivery_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proveedor_id');
            $table->dropColumn('tipo');
        });
    }
};
