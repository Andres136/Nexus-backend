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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_solicitante_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user_asignado_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('producto_id')->nullable()->constrained('products')->onDelete('set null');
            $table->foreignId('departamento_id')->nullable()->constrained('departamentos')->onDelete('set null');
            $table->text('descripcion');
            $table->enum('estado', ['pendiente', 'en_proceso', 'cerrado'])->default('pendiente');
            $table->string('archivo')->nullable();
            $table->enum('prioridad', ['baja', 'media', 'alta'])->default('media');
            $table->dateTime('fecha_solucion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
