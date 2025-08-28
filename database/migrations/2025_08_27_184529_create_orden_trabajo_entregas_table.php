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
        Schema::create('orden_trabajo_entregas', function (Blueprint $table) {
            $table->id();

        $table->foreignId('orden_trabajo_id')
            ->constrained('orden_de_trabajos')
            ->onDelete('cascade');

        $table->foreignId('detalle_id')
            ->constrained('orden__compra__detalles')
            ->onDelete('cascade');

        $table->decimal('cantidad', 10, 2);
        $table->decimal('faltante', 10, 2);
        $table->date('fecha_entrega')->nullable();

        $table->foreignId('usuario_id')
            ->nullable()
            ->constrained('users')
            ->onDelete('set null');

        $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_trabajo_entregas');
        Schema::table('orden_trabajo_entregas', function (Blueprint $table) {
            $table->dropForeign(['orden_trabajo_id']);
            $table->dropForeign(['detalle_id']);
            $table->dropForeign(['usuario_id']);
        });
    }
};
