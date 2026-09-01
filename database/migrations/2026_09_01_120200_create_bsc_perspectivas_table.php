<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las 4 perspectivas del Balanced Scorecard. Se guardan en tabla (no enum)
     * para que el admin pueda cambiar nombre, color y subir un icono propio
     * — mismo patrón que departamentos.icono.
     */
    public function up(): void
    {
        Schema::create('bsc_perspectivas', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique();          // financiera|cliente|procesos|aprendizaje
            $table->string('nombre');
            $table->string('color', 20)->default('#4B5563');
            $table->string('icono')->nullable();         // ruta en storage
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bsc_perspectivas');
    }
};
