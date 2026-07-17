<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividades_operativas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('jornada_operativa_id')->constrained('jornadas_operativas');
            $table->foreignId('user_id')->constrained('users');
            $table->string('tipo');
            $table->foreignId('tarea_id')->nullable()->constrained('tareas')->nullOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias_actividad')->nullOnDelete();
            $table->string('titulo')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('estado')->default('ACTIVA');
            $table->dateTime('inicio_at');
            $table->dateTime('fin_at')->nullable();
            $table->unsignedInteger('segundos')->nullable();
            $table->text('resultado')->nullable();
            $table->text('motivo_bloqueo')->nullable();
            $table->foreignId('creada_por')->constrained('users');
            $table->foreignId('cerrada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'estado']);
            $table->index(['jornada_operativa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades_operativas');
    }
};
