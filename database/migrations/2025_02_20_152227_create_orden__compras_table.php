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
        Schema::create('orden__compras', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_entrega');
            $table->foreignId('cliente_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('estado_id')->constrained()->onDelete('cascade');
            $table->text('ubicacion_entrega');
            $table->text('observaciones');
            $table->decimal('valor_total', 10, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden__compras', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['estado_id']);
        });
        Schema::dropIfExists('orden__compras');
    }
};
