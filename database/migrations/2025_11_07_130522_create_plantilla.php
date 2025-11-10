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
        Schema::create('plantilla', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipo')->nullable(); // Ej: brochure, instructivo, video, catálogo
            $table->longText('contenido_html')->nullable();
            $table->string('video_url')->nullable(); // YouTube, Vimeo, u otro
            $table->json('imagenes')->nullable(); // [{url, titulo}]
            $table->json('logos_empresas')->nullable(); // para varias marcas registradas
            $table->json('certificaciones')->nullable(); // [{nombre, logo, url_cert}]
            $table->json('redes_sociales')->nullable(); // [{nombre, url, icon}]
            $table->json('descargas')->nullable(); // [{nombre, link}]
            $table->boolean('publicada')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantilla');
    }
};
