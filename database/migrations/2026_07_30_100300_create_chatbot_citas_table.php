<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_conversacion_id')->nullable()->constrained('chatbot_conversaciones')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nombre_lead');
            $table->string('email_lead')->nullable();
            $table->string('empresa_lead')->nullable();
            $table->string('telefono_lead')->nullable();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin')->nullable();
            $table->string('titulo')->nullable();
            $table->text('notas')->nullable();
            $table->enum('estado', ['pendiente', 'confirmada', 'cancelada', 'completada'])->default('pendiente');
            $table->enum('creado_por', ['bot', 'agente'])->default('agente');
            $table->timestamps();

            $table->index(['user_id', 'fecha_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_citas');
    }
};
