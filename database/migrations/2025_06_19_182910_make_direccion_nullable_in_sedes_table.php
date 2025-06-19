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
        Schema::table('sedes', function (Blueprint $table) {
            $table->string('nombre')->nullable()->change(); // Cambia la columna 'nombre' a nullable
            $table->string('direccion')->nullable()->change(); // Cambia la columna 'direccion' a nullable

            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sedes', function (Blueprint $table) {
            $table->string('nombre')->nullable(false)->change(); // Cambia la columna 'nombre' a not nullable
            $table->string('direccion')->nullable(false)->change(); // Cambia la columna 'direccion' a not nullable

            //
        });
    }
};