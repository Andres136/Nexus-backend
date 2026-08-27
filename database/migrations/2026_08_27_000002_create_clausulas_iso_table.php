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
        Schema::create('clausulas_iso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('norma_iso_id')->constrained('normas_iso')->onDelete('cascade');
            $table->string('codigo');
            $table->string('descripcion');
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['norma_iso_id', 'codigo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clausulas_iso');
    }
};
