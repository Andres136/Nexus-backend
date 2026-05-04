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
    Schema::create('seguridad_socials', function (Blueprint $table) {
        $table->id();
        $table->uuid('uuid')->unique();
        $table->string('nombre', 45);
        $table->string('nit', 45);
        $table->string('direccion', 45);
        $table->string('fecha_inicio', 45);
        $table->string('fecha_fin', 45)->nullable();
        $table->string('status', 45)->default('activo');
        $table->timestamps();
        $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seguridad_socials');
    }
};
