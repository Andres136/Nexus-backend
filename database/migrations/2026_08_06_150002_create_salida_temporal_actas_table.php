<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salida_temporal_actas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salida_temporal_id')->constrained('salidas_temporales')->onDelete('cascade');
            $table->string('tipo', 20);
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('usuario_nombre')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('generada_por')->nullable()->constrained('users')->onDelete('set null');
            $table->uuid('token')->unique();
            $table->string('estado', 20)->default('pendiente');
            $table->timestamp('generada_at')->nullable();
            $table->timestamp('firmada_at')->nullable();
            $table->string('firma_nombre')->nullable();
            $table->longText('firma_imagen')->nullable();
            $table->string('firma_ip', 45)->nullable();
            $table->string('firma_user_agent', 1000)->nullable();
            $table->timestamps();

            $table->unique(['salida_temporal_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salida_temporal_actas');
    }
};
