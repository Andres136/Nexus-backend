<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correcciones_actividad_operativa', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('actividad_operativa_id')->constrained('actividades_operativas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('motivo', 255);
            $table->text('observaciones')->nullable();
            $table->json('datos_anteriores');
            $table->json('datos_nuevos');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['actividad_operativa_id']);
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correcciones_actividad_operativa');
    }
};
