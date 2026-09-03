<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las 5 etapas de implementación del Balanced Scorecard (rastreador de
     * avance). Estructura fija (marco BSC); lo editable es el estado, el
     * responsable, las fechas y las notas de cada etapa.
     */
    public function up(): void
    {
        Schema::create('bsc_etapas', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('numero');            // 1..5
            $table->string('nombre');
            $table->string('descripcion', 500)->nullable();
            $table->enum('estado', ['pendiente', 'en_progreso', 'completada'])->default('pendiente');
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_objetivo')->nullable();
            $table->date('fecha_completada')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique('numero');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bsc_etapas');
    }
};
