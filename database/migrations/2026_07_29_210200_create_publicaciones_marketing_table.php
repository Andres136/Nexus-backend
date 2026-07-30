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
        Schema::create('publicaciones_marketing', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->foreignId('red_social_id')->constrained('redes_sociales')->cascadeOnDelete();
            $table->foreignId('tipo_post_id')->constrained('tipos_post')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('estado')->default('programado');
            $table->string('link')->nullable();
            $table->text('descripcion')->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publicaciones_marketing');
    }
};
