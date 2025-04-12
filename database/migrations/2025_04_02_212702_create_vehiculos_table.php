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
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('placa')->unique();
            $table->string('marca');
            $table->string('modelo');
            $table->string('tipo');
            $table->year('anio');
            $table->integer('kilometraje_actual');
            $table->enum('estado', ['Activo', 'En mantenimiento', 'Retirado'])->default('Activo');
            $table->text('observaciones')->nullable();
            $table->string('foto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key constraint first
   
        Schema::dropIfExists('vehiculos');
    }
};
