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
        Schema::create('responsabilidades_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('responsabilidad_id')->references('id')->on('responsabilidades')->onDelete('cascade');
            $table->foreignId('bodega_id')->references('id')->on('bodegas')->onDelete('cascade')->nullable();
            $table->foreignId('sede_id')->references('id')->on('sedes')->onDelete('cascade')->nullable();
            $table->boolean('activo')->default(true);
            $table->date('fecha_asignacion')->nullable();
            $table->date('fecha_fin')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('responsabilidades_user');
    }
};
