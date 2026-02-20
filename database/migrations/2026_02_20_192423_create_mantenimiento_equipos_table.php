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
        Schema::create('mantenimiento_equipos', function (Blueprint $table) {

            $table->id();
            $table->foreignId('sede_id')->constrained('sedes')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');

            $table->enum('tipo', ['preventivo', 'correctivo']);

            $table->date('fecha_programada')->nullable();
            $table->date('fecha_ejecucion')->nullable();
            $table->text('observaciones')->nullable();

            $table->enum('estado', ['pendiente', 'en_proceso', 'completado'])
                ->default('pendiente');

            $table->decimal('costo', 10, 2)->nullable();

            $table->timestamps();

            $table->index('sede_id');
            $table->index('producto_id');
            $table->index('empresa_id');
            $table->index('usuario_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mantenimiento_equipos');
    }
};
