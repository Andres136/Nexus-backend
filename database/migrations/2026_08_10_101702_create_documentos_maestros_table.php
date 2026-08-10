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
        Schema::create('documentos_maestros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departamento_id')->constrained('departamentos')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('tipo_documento');
            $table->string('codigo');
            $table->date('fecha_emision');
            $table->date('fecha_actualizacion')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('medio_fisico')->nullable();
            $table->string('medio_digital')->nullable();
            $table->string('retencion_gestion')->nullable();
            $table->string('retencion_central')->nullable();
            $table->string('disposicion_final')->nullable();
            $table->timestamps();

            $table->unique(['departamento_id', 'codigo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos_maestros');
    }
};
