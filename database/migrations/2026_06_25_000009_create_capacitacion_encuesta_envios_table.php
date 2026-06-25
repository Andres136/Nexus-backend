<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capacitacion_encuesta_envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encuesta_id')->constrained('capacitacion_encuestas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('enviado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('token')->unique();
            $table->string('estado', 20)->default('pendiente');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['encuesta_id', 'user_id']);
            $table->index(['estado', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capacitacion_encuesta_envios');
    }
};
