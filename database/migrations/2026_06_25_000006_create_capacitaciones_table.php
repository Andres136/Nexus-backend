<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capacitaciones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->date('fecha_realizacion');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->string('lugar')->nullable();
            $table->string('modalidad', 20)->default('presencial');
            $table->string('estado', 20)->default('programada');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['fecha_realizacion', 'estado']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capacitaciones');
    }
};
