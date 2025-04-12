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
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->onDelete('cascade');
            $table->date('fecha_programada')->nullable();
            $table->date('fecha_realizado')->nullable();
            $table->string('taller')->nullable();
            $table->string('descripcion_trabajo')->nullable();
            $table->decimal('costo', 10, 2)->nullable();
            $table->string('kilometro_programado')->nullable();
            $table->enum('tipo_mantenimiento', ['Preventivo', 'Correctivo']);
            $table->string('archivo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key constraints first
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dropForeign(['vehiculo_id']);
       
        });
        Schema::dropIfExists('mantenimientos');
    }
};
