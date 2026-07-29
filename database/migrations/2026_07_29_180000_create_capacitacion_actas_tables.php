<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capacitacion_actas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('capacitacion_id')->unique()->constrained('capacitaciones')->cascadeOnDelete();
            $table->foreignId('elaborada_por')->constrained('users');
            $table->string('numero', 80)->unique();
            $table->string('titulo');
            $table->text('objetivo')->nullable();
            $table->longText('desarrollo');
            $table->json('compromisos')->nullable();
            $table->text('conclusiones')->nullable();
            $table->timestamp('publicada_at')->nullable();
            $table->timestamps();
        });

        Schema::create('capacitacion_acta_envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acta_id')->constrained('capacitacion_actas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('enviado_por')->constrained('users');
            $table->uuid('token')->unique();
            $table->string('estado', 20)->default('pendiente');
            $table->timestamp('enviada_at')->nullable();
            $table->timestamp('firmada_at')->nullable();
            $table->string('firma_nombre')->nullable();
            $table->longText('firma_imagen')->nullable();
            $table->string('firma_ip', 45)->nullable();
            $table->text('firma_user_agent')->nullable();
            $table->timestamps();

            $table->unique(['acta_id', 'user_id']);
            $table->index(['acta_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capacitacion_acta_envios');
        Schema::dropIfExists('capacitacion_actas');
    }
};
