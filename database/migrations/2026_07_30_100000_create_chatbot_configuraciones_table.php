<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->default('Asistente virtual');
            $table->string('avatar_url')->nullable();
            $table->text('mensaje_bienvenida');
            $table->text('prompt_sistema');
            $table->foreignId('departamento_id')->nullable()->constrained('departamentos')->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->string('openai_model')->default('gpt-4o-mini');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_configuraciones');
    }
};
