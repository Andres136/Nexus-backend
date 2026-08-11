<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_events', function (Blueprint $table) {
            $table->foreignId('orden_servicio_id')
                ->nullable()
                ->unique()
                ->after('proveedor_id')
                ->constrained('ordenes_servicio')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('delivery_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('orden_servicio_id');
        });
    }
};
