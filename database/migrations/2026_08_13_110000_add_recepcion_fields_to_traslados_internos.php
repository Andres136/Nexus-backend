<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('envios_internos', function (Blueprint $table) {
            $table->dateTime('fecha_recepcion')->nullable()->after('fecha_envio');
        });

        Schema::table('detalles_envio_internos', function (Blueprint $table) {
            $table->foreignId('bodega_destino_id')
                ->nullable()
                ->after('bodega_origen_id')
                ->constrained('bodegas')
                ->nullOnDelete();
            $table->decimal('cantidad_recibida', 15, 2)->nullable()->after('cantidad');
        });
    }

    public function down(): void
    {
        Schema::table('detalles_envio_internos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bodega_destino_id');
            $table->dropColumn('cantidad_recibida');
        });

        Schema::table('envios_internos', function (Blueprint $table) {
            $table->dropColumn('fecha_recepcion');
        });
    }
};
