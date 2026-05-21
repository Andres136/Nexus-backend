<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarea_seguimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_id')->constrained('tareas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('nota')->nullable();
            $table->unsignedTinyInteger('estado_anterior')->nullable();
            $table->unsignedTinyInteger('estado_nuevo')->nullable();
            $table->enum('tipo', ['cambio_estado', 'nota'])->default('nota');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarea_seguimientos');
    }
};
