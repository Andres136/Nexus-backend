<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('soporte_tareas', function (Blueprint $table) {

            $table->id();

            $table->string('soporte_tarea')->nullable();

            $table->foreignId('tarea_id')
                ->nullable()
                ->constrained('tareas')
                ->nullOnDelete();

            $table->foreignId('hallazgo_id')
                ->nullable()
                ->constrained('hallazgo_novedades')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soporte_tareas');
    }
};