<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_conversaciones', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->string('nombre_lead')->nullable();
            $table->string('email_lead')->nullable();
            $table->string('empresa_lead')->nullable();
            $table->string('telefono_lead')->nullable();
            $table->enum('estado', ['bot', 'esperando_humano', 'asignada', 'cerrada'])->default('bot');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('asignado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('asignado_at')->nullable();
            $table->string('origen_url')->nullable();
            $table->string('dominio')->nullable();
            $table->string('ip')->nullable();
            $table->timestamp('ultima_actividad_at')->nullable();
            $table->timestamp('cerrada_at')->nullable();
            $table->timestamps();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_conversaciones');
    }
};
