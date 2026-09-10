<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configuración única de la ventana pública que se renderiza en el sitio web.
     */
    public function up(): void
    {
        Schema::create('ventana_web', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('titulo')->nullable();
            $table->string('subtitulo')->nullable();
            $table->text('contenido')->nullable();
            $table->string('imagen')->nullable();
            $table->boolean('activo')->default(false);
            $table->boolean('boton_activo')->default(false);
            $table->string('boton_texto')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventana_web');
    }
};
