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
        Schema::create('seguimiento_clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('tipo_contacto'); // Ej: Llamada, WhatsApp, Email
            $table->string('estado');// Ej: Interesado, No responde, Cierre exitoso
            $table->text('comentario')->nullable(); // Notas sobre la conversación
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

       Schema::table('seguimiento_clientes', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::dropIfExists('seguimiento_clientes');
    }
};
