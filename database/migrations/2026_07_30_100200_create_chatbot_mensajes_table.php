<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_conversacion_id')->constrained('chatbot_conversaciones')->cascadeOnDelete();
            $table->enum('remitente', ['lead', 'bot', 'agente']);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('contenido');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['chatbot_conversacion_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_mensajes');
    }
};
