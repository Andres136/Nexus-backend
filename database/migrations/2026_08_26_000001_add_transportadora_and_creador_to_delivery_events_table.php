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
            // Quién creó el evento: solo esta persona puede cerrarlo (ver DeliveryEventController::changeStatus).
            $table->foreignId('creado_por')->nullable()->after('usuario_id')
                ->constrained('users')->nullOnDelete();

            // Envío por transportadora externa: no hay vehículo/usuario propio manejando, sino
            // los datos de quien recibe/transporta por parte de la transportadora.
            $table->boolean('es_transportadora')->default(false)->after('vehiculo_id');
            $table->string('transportadora_guia', 100)->nullable()->after('es_transportadora');
            $table->string('transportadora_nombre', 150)->nullable()->after('transportadora_guia');
            $table->string('transportadora_cedula', 30)->nullable()->after('transportadora_nombre');
            $table->string('transportadora_placa', 20)->nullable()->after('transportadora_cedula');
        });

        // Un envío por transportadora no tiene vehículo propio: la FK debe permitir NULL.
        // (raw SQL para no depender de doctrine/dbal, que no está instalado en el proyecto)
        DB::statement('ALTER TABLE delivery_events MODIFY vehiculo_id BIGINT UNSIGNED NULL');

        // Los eventos existentes no tienen creador registrado: se asume el usuario responsable
        // como creador, para no dejarlos bloqueados sin nadie que los pueda cerrar.
        DB::table('delivery_events')->whereNull('creado_por')->update([
            'creado_por' => DB::raw('usuario_id'),
        ]);
    }

    public function down(): void
    {
        Schema::table('delivery_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('creado_por');
            $table->dropColumn([
                'es_transportadora',
                'transportadora_guia',
                'transportadora_nombre',
                'transportadora_cedula',
                'transportadora_placa',
            ]);
        });

        DB::statement('ALTER TABLE delivery_events MODIFY vehiculo_id BIGINT UNSIGNED NOT NULL');
    }
};
