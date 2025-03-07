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
        Schema::create('orden_de_trabajos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('orden__compras')->onDelete('cascade');
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->date('fecha_entrega');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('observaciones')->nullable();
            $table->decimal('valor_total', 10, 2)->default(0);
            $table->foreignId('estado_id')->constrained('estados')->onDelete('cascade');
            $table->integer('faltantes')->nullable();
            $table->timestamps();
        });    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden_de_trabajos', function (Blueprint $table) {
            $table->dropForeign(['orden_compra_id']);
            $table->dropForeign(['cliente_id']);
            $table->dropForeign(['estado_id']);
            $table->dropForeign(['user_id']);
        });
        Schema::dropIfExists('orden_de_trabajos');
    }
};
