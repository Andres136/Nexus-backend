<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orden__compras', function (Blueprint $table) {
            // Cotización de la que se generó esta Orden de Compra (opcional).
            $table->foreignId('cotizacion_id')
                ->nullable()
                ->after('empresa_id')
                ->constrained('cotizaciones')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orden__compras', function (Blueprint $table) {
            $table->dropForeign(['cotizacion_id']);
            $table->dropColumn('cotizacion_id');
        });
    }
};
