<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historial de correcciones al total enviado de un detalle de la OT:
     * quién lo corrigió, de cuánto a cuánto y por qué.
     */
    public function up(): void
    {
        Schema::create('orden_trabajo_entrega_correcciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('orden_trabajo_id')
                ->constrained('orden_de_trabajos')
                ->cascadeOnDelete();

            $table->foreignId('detalle_id')
                ->constrained('orden__compra__detalles')
                ->cascadeOnDelete();

            $table->decimal('cantidad_anterior', 10, 2);
            $table->decimal('cantidad_nueva', 10, 2);
            $table->text('motivo');

            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_trabajo_entrega_correcciones');
    }
};
