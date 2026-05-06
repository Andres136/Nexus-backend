<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jornada_laborals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->integer('horas_semanales');
            $table->string('nombre', 100);      // "Tiempo completo", "Medio tiempo"
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jornada_laborals');
    }
};