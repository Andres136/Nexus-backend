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
        Schema::create('pqrs', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('empresa');
            $table->string('email');
            $table->string('telefono');
            $table->string('mensaje');
            $table->foreignId('estado_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {   Schema::table('pqrs', function (Blueprint $table) {
            // Eliminar claves foráneas
            $table->dropForeign(['estado_id']);
            // Eliminar columnas
            $table->dropColumn(['estado_id']);
        });
        // Luego, eliminar la tabla si es necesario
        Schema::dropIfExists('pqrs');
    }
};
