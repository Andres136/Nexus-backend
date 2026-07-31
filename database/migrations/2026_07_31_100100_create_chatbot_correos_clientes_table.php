<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_correos_clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('destinatario');
            $table->string('asunto');
            $table->text('mensaje');
            $table->string('estado', 20)->default('enviado');
            $table->text('error')->nullable();
            $table->timestamp('enviado_at')->nullable();
            $table->timestamps();
            $table->index(['cliente_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_correos_clientes');
    }
};
