<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Datos capturados por el formulario público de la ventana web.
     */
    public function up(): void
    {
        Schema::create('ventana_web_registros', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ventana_web_id')->constrained('ventana_web')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('correo');
            $table->string('telefono');
            $table->text('mensaje')->nullable();
            $table->boolean('leido')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventana_web_registros');
    }
};
