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
        Schema::table('movimientos_stock', function (Blueprint $table) {
            $table->foreignId('envio_interno_id')
            ->nullable()
            ->constrained('envios_internos')->after('orden_compra_id')
            ->nullOnDelete();
              // Sedes explícitas del movimiento (para filtros y auditoría)
            $table->foreignId('sede_origen_id')
                ->nullable()
                ->constrained('sedes')
                ->nullOnDelete();

            $table->foreignId('sede_destino_id')
                ->nullable()
                ->constrained('sedes')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimientos_stock', function (Blueprint $table) {
            $table->dropForeign(['envio_interno_id']);
            $table->dropForeign(['sede_origen_id']);
            $table->dropForeign(['sede_destino_id']);
            $table->dropColumn(['envio_interno_id', 'sede_origen_id', 'sede_destino_id']);
        });
    }
};
